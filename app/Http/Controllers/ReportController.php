<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = $request->input('tab', 'faturamento');

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $year = (int) $request->input('year', Carbon::now()->year);

        $data = match ($tab) {
            'atendimentos' => $this->getAppointmentsReport($user->id, $startDate, $endDate),
            'clientes' => $this->getClientsReport($user->id, $startDate, $endDate),
            'anual' => $this->getAnnualReport($user->id, $year),
            default => $this->getRevenueReport($user->id, $startDate, $endDate),
        };

        return Inertia::render('Reports/Index', [
            'activeTab' => $tab,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'year' => $year,
            ],
            'report' => $data,
        ]);
    }

    private function getRevenueReport(int $userId, string $startDate, string $endDate): array
    {
        $incomeCents = (int) Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount_cents');

        $expenseCents = (int) Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount_cents');

        $byCategory = Transaction::where('transactions.user_id', $userId)
            ->whereBetween('transactions.occurred_on', [$startDate, $endDate])
            ->leftJoin('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->select(
                'transactions.type',
                DB::raw('COALESCE(transaction_categories.name, "Sem Categoria") as category_name'),
                DB::raw('SUM(transactions.amount_cents) as total_cents'),
                DB::raw('COUNT(transactions.id) as count')
            )
            ->groupBy('transactions.type', 'category_name')
            ->orderByDesc('total_cents')
            ->get();

        $byPaymentMethod = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->select('payment_method', DB::raw('SUM(amount_cents) as total_cents'), DB::raw('COUNT(id) as count'))
            ->groupBy('payment_method')
            ->get();

        return [
            'income_cents' => $incomeCents,
            'expense_cents' => $expenseCents,
            'net_cents' => $incomeCents - $expenseCents,
            'by_category' => $byCategory,
            'by_payment_method' => $byPaymentMethod,
        ];
    }

    private function getAppointmentsReport(int $userId, string $startDate, string $endDate): array
    {
        $appointments = Appointment::where('user_id', $userId)
            ->whereDate('service_at', '>=', $startDate)
            ->whereDate('service_at', '<=', $endDate)
            ->get();

        $total = $appointments->count();
        $inProgress = $appointments->where('status.value', 'in_progress')->count();
        $completed = $appointments->where('status.value', 'completed')->count();
        $canceled = $appointments->where('status.value', 'canceled')->count();

        $expectedTotalCents = (int) $appointments->sum('expected_amount_cents');

        return [
            'total_appointments' => $total,
            'in_progress_count' => $inProgress,
            'completed_count' => $completed,
            'canceled_count' => $canceled,
            'expected_total_cents' => $expectedTotalCents,
        ];
    }

    private function getClientsReport(int $userId, string $startDate, string $endDate): array
    {
        // Top clientes por faturamento de entradas no período
        $topClients = Client::where('clients.user_id', $userId)
            ->join('transactions', 'clients.id', '=', 'transactions.client_id')
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.occurred_on', [$startDate, $endDate])
            ->select(
                'clients.id',
                'clients.name',
                'clients.phone_e164',
                DB::raw('SUM(transactions.amount_cents) as total_revenue_cents'),
                DB::raw('COUNT(transactions.id) as transactions_count')
            )
            ->groupBy('clients.id', 'clients.name', 'clients.phone_e164')
            ->orderByDesc('total_revenue_cents')
            ->limit(10)
            ->get();

        return [
            'top_clients' => $topClients,
        ];
    }

    private function getAnnualReport(int $userId, int $year): array
    {
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($year, $m, 1)->endOfMonth()->toDateString();

            $income = (int) Transaction::where('user_id', $userId)
                ->where('type', 'income')
                ->whereBetween('occurred_on', [$start, $end])
                ->sum('amount_cents');

            $expense = (int) Transaction::where('user_id', $userId)
                ->where('type', 'expense')
                ->whereBetween('occurred_on', [$start, $end])
                ->sum('amount_cents');

            $months[] = [
                'month' => $m,
                'name' => Carbon::create($year, $m, 1)->locale('pt_BR')->translatedFormat('M'),
                'income_cents' => $income,
                'expense_cents' => $expense,
                'net_cents' => $income - $expense,
            ];
        }

        $totalYearIncome = array_sum(array_column($months, 'income_cents'));
        $totalYearExpense = array_sum(array_column($months, 'expense_cents'));

        return [
            'year' => $year,
            'months' => $months,
            'total_income_cents' => $totalYearIncome,
            'total_expense_cents' => $totalYearExpense,
            'total_net_cents' => $totalYearIncome - $totalYearExpense,
        ];
    }
}
