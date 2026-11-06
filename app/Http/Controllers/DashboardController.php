<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $businessProfile = $user->businessProfile;
        $totalClients = Client::where('user_id', $user->id)->count();

        // Onboarding checklist
        $checklist = [
            'store_profile_completed' => ! empty($businessProfile?->store_name),
            'first_client_added' => $totalClients > 0,
        ];

        // Estatísticas do comerciante (preparado para vendas e despesas do Sprint 2)
        $metrics = [
            'total_clients' => $totalClients,
            'store_name' => $businessProfile?->store_name ?: $businessProfile?->display_name ?: 'Minha Loja',
            'activity_description' => $businessProfile?->activity_description,
            'recent_clients' => Client::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'phone_e164', 'created_at']),
        ];

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'checklist' => $checklist,
        ]);
    }
}
