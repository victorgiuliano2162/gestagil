<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\TransactionRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->input('type');
        $month = $request->input('month', now()->format('Y-m'));

        $startOfMonth = Carbon::parse($month.'-01')->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($month.'-01')->endOfMonth()->toDateString();

        $query = Transaction::query()
            ->with(['category:id,name,color', 'client:id,name'])
            ->withExists(['receipts as has_active_receipt' => fn ($q) => $q->where('status', 'issued')])
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->when($type, fn ($q, $t) => $q->where('type', $t));

        // Totais do mês
        $totalIncomeCents = (int) Transaction::where('type', 'income')
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $totalExpenseCents = (int) Transaction::where('type', 'expense')
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $transactions = (clone $query)
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'summary' => [
                'income_cents' => $totalIncomeCents,
                'expense_cents' => $totalExpenseCents,
                'net_cents' => $totalIncomeCents - $totalExpenseCents,
            ],
            'filters' => [
                'type' => $type,
                'month' => $month,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $type = $request->input('type', 'income');
        $appointmentId = $request->integer('appointment_id') ?: null;

        $categories = TransactionCategory::orderBy('name')->get(['id', 'name', 'color', 'type']);
        $clients = Client::orderBy('name')->get(['id', 'name']);

        $defaultData = [
            'type' => $type,
            'appointment_id' => $appointmentId,
            'client_id' => null,
            'description' => '',
            'amount' => '',
        ];

        if ($appointmentId) {
            $appointment = Appointment::with('client')->find($appointmentId);
            if ($appointment) {
                $defaultData['client_id'] = $appointment->client_id;
                $defaultData['description'] = "Recebimento: {$appointment->service_description}";
                if ($appointment->expected_amount_cents) {
                    $defaultData['amount'] = number_format($appointment->expected_amount_cents / 100, 2, '.', '');
                }
            }
        }

        return Inertia::render('Transactions/Create', [
            'categories' => $categories,
            'clients' => $clients,
            'defaultData' => $defaultData,
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ]),
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['amount_cents'] = (int) round($data['amount'] * 100);
        unset($data['amount']);

        $this->resolveAppointmentClient($data);

        Transaction::create($data);

        return redirect()->route('transactions.index')
            ->with('success', 'Lançamento registrado com sucesso!');
    }

    public function edit(Transaction $transaction): Response
    {
        $categories = TransactionCategory::orderBy('name')->get(['id', 'name', 'color', 'type']);
        $clients = Client::orderBy('name')->get(['id', 'name']);
        $hasActiveReceipt = $transaction->receipts()->where('status', 'issued')->exists();

        return Inertia::render('Transactions/Edit', [
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => number_format($transaction->amount_cents / 100, 2, '.', ''),
                'occurred_on' => $transaction->occurred_on->format('Y-m-d'),
                'description' => $transaction->description,
                'category_id' => $transaction->category_id,
                'payment_method' => $transaction->payment_method->value,
                'client_id' => $transaction->client_id,
                'appointment_id' => $transaction->appointment_id,
                'notes' => $transaction->notes,
            ],
            'categories' => $categories,
            'clients' => $clients,
            'hasActiveReceipt' => $hasActiveReceipt,
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ]),
        ]);
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validated();
        $newAmountCents = (int) round($data['amount'] * 100);
        $hasActiveReceipt = $transaction->receipts()->where('status', 'issued')->exists();

        if ($hasActiveReceipt) {
            $lockedChanged = $data['type'] !== $transaction->type
                || $newAmountCents !== $transaction->amount_cents
                || $data['occurred_on'] !== $transaction->occurred_on->format('Y-m-d')
                || $data['payment_method'] !== $transaction->payment_method->value
                || $data['description'] !== $transaction->description;

            if ($lockedChanged) {
                return back()->with('error', 'Este lançamento possui recibo ativo. Cancele o recibo antes de alterar tipo, valor, data, forma de pagamento ou descrição.');
            }

            $transaction->update([
                'category_id' => $data['category_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return redirect()->route('transactions.index')
                ->with('success', 'Lançamento atualizado com sucesso!');
        }

        $data['amount_cents'] = $newAmountCents;
        unset($data['amount']);

        $this->resolveAppointmentClient($data);

        $transaction->update($data);

        return redirect()->route('transactions.index')
            ->with('success', 'Lançamento atualizado com sucesso!');
    }

    /**
     * Valida vínculo com atendimento (RN-05) e garante consistência do cliente (RN-07).
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveAppointmentClient(array &$data): void
    {
        if (empty($data['appointment_id'])) {
            return;
        }

        $appt = Appointment::findOrFail($data['appointment_id']);

        if ($appt->status === AppointmentStatus::Canceled) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Não é possível vincular um lançamento a um atendimento cancelado.',
            ]);
        }

        $data['client_id'] = $appt->client_id;
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        if ($transaction->receipts()->where('status', 'issued')->exists()) {
            return back()->with('error', 'Não é possível excluir um lançamento com recibo ativo. Cancele o recibo antes.');
        }

        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Lançamento removido com sucesso!');
    }
}
