<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;

test('authenticated user can list cash flow transactions', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $category = $user->categories()->where('type', 'income')->first();

    Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount_cents' => 15000,
        'description' => 'Serviço elétrico automotivo',
        'occurred_on' => now()->toDateString(),
        'payment_method' => PaymentMethod::Pix,
    ]);

    $response = $this->actingAs($user)->get(route('transactions.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Transactions/Index')
            ->has('transactions.data', 1)
            ->where('summary.income_cents', 15000)
            ->where('summary.net_cents', 15000)
        );
});

test('user can record income and expense with formatted amount', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $incomeCategory = $user->categories()->where('type', 'income')->first();
    $expenseCategory = $user->categories()->where('type', 'expense')->first();

    // 1. Cadastrar Entrada
    $incomeResponse = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '250,50',
        'description' => 'Venda de peças e reparo',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'category_id' => $incomeCategory->id,
    ]);

    $incomeResponse->assertRedirect(route('transactions.index'));
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 25050,
        'description' => 'Venda de peças e reparo',
        'payment_method' => 'pix',
    ]);

    // 2. Cadastrar Despesa
    $expenseResponse = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'expense',
        'amount' => '100,00',
        'description' => 'Compra de cabos elétricos',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'cash',
        'category_id' => $expenseCategory->id,
    ]);

    $expenseResponse->assertRedirect(route('transactions.index'));
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'expense',
        'amount_cents' => 10000,
        'payment_method' => 'cash',
    ]);
});

test('transactions enforce multi-tenant isolation', function () {
    $userA = User::factory()->withDefaultCategories()->create();
    $userB = User::factory()->withDefaultCategories()->create();

    $catA = $userA->categories()->first();
    $transA = Transaction::create([
        'user_id' => $userA->id,
        'category_id' => $catA->id,
        'type' => 'income',
        'amount_cents' => 50000,
        'description' => 'Receita Secreta de A',
        'occurred_on' => now()->toDateString(),
        'payment_method' => PaymentMethod::Cash,
    ]);

    // Usuário B não vê a transação de A no index
    $response = $this->actingAs($userB)->get(route('transactions.index'));
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('transactions.data', 0)
            ->where('summary.income_cents', 0)
        );

    // Usuário B não consegue excluir a transação de A
    $deleteResponse = $this->actingAs($userB)->delete(route('transactions.destroy', $transA->id));
    $deleteResponse->assertNotFound();

    $this->assertDatabaseHas('transactions', [
        'id' => $transA->id,
        'deleted_at' => null,
    ]);
});

test('transaction can be linked to appointment and client', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Roberto Carlos',
    ]);

    $appointment = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Revisão de Alternador',
        'service_at' => now(),
        'expected_amount_cents' => 38000,
        'status' => AppointmentStatus::Completed,
    ]);

    $incomeCategory = $user->categories()->where('type', 'income')->first();

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '380,00',
        'description' => 'Pagamento de Revisão de Alternador',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'category_id' => $incomeCategory->id,
        'client_id' => $client->id,
        'appointment_id' => $appointment->id,
    ]);

    $response->assertRedirect(route('transactions.index'));

    $transaction = Transaction::where('appointment_id', $appointment->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->client_id)->toBe($client->id)
        ->and($transaction->amount_cents)->toBe(38000);
});

test('user can soft delete transaction', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $cat = $user->categories()->first();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'category_id' => $cat->id,
        'type' => 'expense',
        'amount_cents' => 4500,
        'description' => 'Despesa para cancelar',
        'occurred_on' => now()->toDateString(),
        'payment_method' => PaymentMethod::Other,
    ]);

    $response = $this->actingAs($user)->delete(route('transactions.destroy', $trans->id));
    $response->assertRedirect(route('transactions.index'));

    $this->assertSoftDeleted('transactions', ['id' => $trans->id]);
});
