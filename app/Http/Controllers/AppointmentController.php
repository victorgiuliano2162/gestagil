<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $search = $request->input('search');
        $clientId = $request->integer('client_id') ?: null;
        $dateFrom = $request->input('date_from') ?: null;
        $dateTo = $request->input('date_to') ?: null;

        $appointments = Appointment::query()
            ->with('client:id,name,phone_e164')
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($clientId, fn ($q, $c) => $q->where('client_id', $c))
            ->when($dateFrom, fn ($q, $df) => $q->whereDate('service_at', '>=', $df))
            ->when($dateTo, fn ($q, $dt) => $q->whereDate('service_at', '<=', $dt))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('service_description', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQ) use ($search) {
                            $clientQ->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('service_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'client_id' => $clientId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statuses' => collect(AppointmentStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'phone_e164']);
        $incomeCategories = TransactionCategory::where('type', 'income')
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Appointments/Create', [
            'clients' => $clients,
            'selectedClientId' => $request->integer('client_id') ?: null,
            'incomeCategories' => $incomeCategories,
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ]),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $isCompletedAndPaid = (bool) ($data['is_completed_and_paid'] ?? false);
        $issueReceipt = $isCompletedAndPaid && (bool) ($data['issue_receipt'] ?? false);
        $paymentMethod = $data['payment_method'] ?? 'pix';
        $categoryId = $data['category_id'] ?? null;

        unset($data['is_completed_and_paid'], $data['issue_receipt'], $data['payment_method'], $data['category_id']);

        if (isset($data['expected_amount'])) {
            $data['expected_amount_cents'] = (int) round($data['expected_amount'] * 100);
            unset($data['expected_amount']);
        }

        if ($isCompletedAndPaid) {
            $data['status'] = AppointmentStatus::Completed;
            $data['completed_at'] = now();
        }

        [$appointment, $transaction] = DB::transaction(function () use ($data, $isCompletedAndPaid, $paymentMethod, $categoryId) {
            $appointment = Appointment::create($data);
            $transaction = null;

            if ($isCompletedAndPaid) {
                $transaction = Transaction::create([
                    'type' => 'income',
                    'amount_cents' => $appointment->expected_amount_cents,
                    'occurred_on' => now()->toDateString(),
                    'description' => "Recebimento: {$appointment->service_description}",
                    'payment_method' => $paymentMethod,
                    'category_id' => $categoryId,
                    'client_id' => $appointment->client_id,
                    'appointment_id' => $appointment->id,
                ]);
            }

            return [$appointment, $transaction];
        });

        if ($issueReceipt && $transaction) {
            return redirect()->route('receipts.create', ['transaction_id' => $transaction->id])
                ->with('success', 'Atendimento concluído e recebimento registrado! Revise os dados para emitir o recibo.');
        }

        if ($isCompletedAndPaid) {
            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Atendimento concluído e recebimento registrado no caixa!');
        }

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Atendimento agendado com sucesso!');
    }

    public function show(Appointment $appointment): Response
    {
        $appointment->load(['client', 'transactions.category', 'transactions.activeReceipt']);

        return Inertia::render('Appointments/Show', [
            'appointment' => [
                ...$appointment->toArray(),
                'status_label' => $appointment->status->label(),
                'status_color' => $appointment->status->color(),
            ],
            'transactions' => $appointment->transactions->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount_cents' => $t->amount_cents,
                'occurred_on' => $t->occurred_on->format('Y-m-d'),
                'description' => $t->description,
                'payment_method_label' => $t->payment_method->label(),
                'category' => $t->category ? [
                    'id' => $t->category->id,
                    'name' => $t->category->name,
                ] : null,
                'active_receipt' => $t->activeReceipt ? [
                    'id' => $t->activeReceipt->id,
                    'formatted_number' => $t->activeReceipt->formatted_number,
                ] : null,
            ])->values(),
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'phone_e164']);

        return Inertia::render('Appointments/Edit', [
            'appointment' => [
                ...$appointment->toArray(),
                'expected_amount' => $appointment->expected_amount_cents ? $appointment->expected_amount_cents / 100 : null,
                'service_at_formatted' => $appointment->service_at->format('Y-m-d\TH:i'),
            ],
            'clients' => $clients,
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['expected_amount'])) {
            $data['expected_amount_cents'] = (int) round($data['expected_amount'] * 100);
            unset($data['expected_amount']);
        } else {
            $data['expected_amount_cents'] = null;
        }

        $appointment->update($data);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Atendimento atualizado com sucesso!');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = AppointmentStatus::tryFrom($validated['status']);
        if (! $newStatus) {
            return back()->withErrors(['status' => 'Status inválido.']);
        }

        if (! $appointment->status->canTransitionTo($newStatus)) {
            return back()->withErrors(['status' => "Não é possível alterar de {$appointment->status->label()} para {$newStatus->label()}."]);
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === AppointmentStatus::Completed) {
            $updateData['completed_at'] = now();
        } elseif ($newStatus === AppointmentStatus::Canceled) {
            $updateData['canceled_at'] = now();
            $updateData['cancel_reason'] = $validated['cancel_reason'] ?? null;
        }

        $appointment->update($updateData);

        return back()->with('success', 'Status do atendimento atualizado!');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        if ($appointment->transactions()->exists()) {
            return back()->with('error', 'Não é possível excluir um atendimento que possui lançamentos vinculados.');
        }

        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Atendimento removido com sucesso!');
    }
}
