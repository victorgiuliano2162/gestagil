<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;

test('authenticated user can view revenue report with proper calculations', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $now = now()->toDateString();

    Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 30000,
        'occurred_on' => $now,
        'description' => 'Serviço de Freios',
        'payment_method' => PaymentMethod::Pix,
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'type' => 'expense',
        'amount_cents' => 8000,
        'occurred_on' => $now,
        'description' => 'Compra de Fluido de Freio',
        'payment_method' => PaymentMethod::Cash,
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', ['tab' => 'faturamento']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('activeTab', 'faturamento')
            ->where('report.income_cents', 30000)
            ->where('report.expense_cents', 8000)
            ->where('report.net_cents', 22000)
        );
});

test('user can view appointments and services report', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Marcos Lima']);

    Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Alinhamento',
        'service_at' => now(),
        'expected_amount_cents' => 12000,
        'status' => AppointmentStatus::Completed,
    ]);

    Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Troca de Correia',
        'service_at' => now(),
        'expected_amount_cents' => 25000,
        'status' => AppointmentStatus::InProgress,
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', ['tab' => 'atendimentos']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'atendimentos')
            ->where('report.total_appointments', 2)
            ->where('report.completed_count', 1)
            ->where('report.in_progress_count', 1)
            ->where('report.expected_total_cents', 37000)
        );
});

test('user can view top clients ranking report', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $clientGold = Client::create(['user_id' => $user->id, 'name' => 'Cliente Ouro']);
    $clientSilver = Client::create(['user_id' => $user->id, 'name' => 'Cliente Prata']);

    Transaction::create([
        'user_id' => $user->id,
        'client_id' => $clientGold->id,
        'type' => 'income',
        'amount_cents' => 90000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Compra Grande',
        'payment_method' => PaymentMethod::Pix,
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'client_id' => $clientSilver->id,
        'type' => 'income',
        'amount_cents' => 30000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Compra Média',
        'payment_method' => PaymentMethod::Card,
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', ['tab' => 'clientes']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'clientes')
            ->has('report.top_clients', 2)
            ->where('report.top_clients.0.name', 'Cliente Ouro')
            ->where('report.top_clients.0.total_revenue_cents', 90000)
            ->where('report.top_clients.1.name', 'Cliente Prata')
            ->where('report.top_clients.1.total_revenue_cents', 30000)
        );
});

test('user can view annual breakdown report', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $year = (int) now()->format('Y');

    Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 50000,
        'occurred_on' => "{$year}-03-15",
        'description' => 'Venda de Março',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', ['tab' => 'anual', 'year' => $year]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'anual')
            ->where('report.year', $year)
            ->has('report.months', 12)
            ->where('report.total_income_cents', 50000)
        );
});

test('reports guarantee multi-tenant data isolation', function () {
    $userA = User::factory()->withDefaultCategories()->create();
    $userB = User::factory()->withDefaultCategories()->create();

    Transaction::create([
        'user_id' => $userA->id,
        'type' => 'income',
        'amount_cents' => 99999,
        'occurred_on' => now()->toDateString(),
        'description' => 'Receita Secreta de A',
        'payment_method' => PaymentMethod::Pix,
    ]);

    // Usuário B gera o relatório de faturamento: receita deve ser ZERO
    $response = $this->actingAs($userB)->get(route('reports.index', ['tab' => 'faturamento']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.income_cents', 0)
            ->where('report.expense_cents', 0)
        );
});
