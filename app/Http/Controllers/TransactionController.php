<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\TransactionRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->input('type');
        $month = $request->input('month', now()->format('Y-m'));

        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        $query = Transaction::query()
            ->with(['category:id,name,color', 'client:id,name'])
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

        $categories = TransactionCategory::where('type', $type)->orderBy('name')->get(['id', 'name', 'color']);
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

        // Herança automática de cliente a partir do atendimento se não fornecido
        if (! empty($data['appointment_id']) && empty($data['client_id'])) {
            $appt = Appointment::find($data['appointment_id']);
            if ($appt) {
                $data['client_id'] = $appt->client_id;
            }
        }

        Transaction::create($data);

        return redirect()->route('transactions.index')
            ->with('success', 'Lançamento registrado com sucesso!');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Lançamento removido com sucesso!');
    }
}
