<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $businessProfile = $user->businessProfile()->first();
        $totalClients = Client::where('user_id', $user->id)->count();

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $monthIncomeCents = (int) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $monthExpenseCents = (int) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->sum('amount_cents');

        $inProgressAppointments = Appointment::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->count();

        // Onboarding checklist
        $checklist = [
            'store_profile_completed' => ! empty($businessProfile?->store_name),
            'first_client_added' => $totalClients > 0,
            'first_transaction_recorded' => Transaction::where('user_id', $user->id)->exists(),
        ];

        // Estatísticas do comerciante
        $metrics = [
            'total_clients' => $totalClients,
            'store_name' => $businessProfile?->store_name ?: $businessProfile?->display_name ?: 'Minha Loja',
            'activity_description' => $businessProfile?->activity_description,
            'month_income_cents' => $monthIncomeCents,
            'month_expense_cents' => $monthExpenseCents,
            'month_balance_cents' => $monthIncomeCents - $monthExpenseCents,
            'in_progress_appointments' => $inProgressAppointments,
            'recent_clients' => Client::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'phone_e164', 'created_at']),
            'recent_transactions' => Transaction::where('user_id', $user->id)
                ->with(['category:id,name', 'client:id,name'])
                ->latest('occurred_on')
                ->take(5)
                ->get(),
        ];

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'checklist' => $checklist,
        ]);
    }
}
