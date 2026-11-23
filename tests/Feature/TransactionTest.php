<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

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

test('transaction cannot reference client, appointment or category belonging to another user', function () {
    $userA = User::factory()->withDefaultCategories()->create();
    $userB = User::factory()->withDefaultCategories()->create();

    $catA = $userA->categories()->where('type', 'income')->first();
    $clientA = Client::create([
        'user_id' => $userA->id,
        'name' => 'Cliente A',
    ]);
    $apptA = Appointment::create([
        'user_id' => $userA->id,
        'client_id' => $clientA->id,
        'service_description' => 'Serviço A',
        'service_at' => now(),
    ]);

    $response = $this->actingAs($userB)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '100,00',
        'description' => 'Tentativa cross-tenant',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'category_id' => $catA->id,
        'client_id' => $clientA->id,
        'appointment_id' => $apptA->id,
    ]);

    $response->assertSessionHasErrors(['category_id', 'client_id', 'appointment_id']);
    expect(Transaction::withoutGlobalScopes()->where('user_id', $userB->id)->count())->toBe(0);
});

test('cannot delete transaction with active receipt until receipt is canceled', function () {
    $user = User::factory()->withDefaultCategories()->create();

    $trans = Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 25000,
        'description' => 'Serviço com recibo',
        'occurred_on' => now()->toDateString(),
        'payment_method' => PaymentMethod::Pix,
    ]);

    $receipt = Receipt::create([
        'user_id' => $user->id,
        'transaction_id' => $trans->id,
        'code' => Str::uuid()->toString(),
        'year' => (int) now()->format('Y'),
        'number' => 1,
        'status' => 'issued',
        'issued_at' => now(),
        'amount_cents' => 25000,
        'payment_method' => 'pix',
        'description' => 'Serviço com recibo',
        'payer_name' => 'Pagador',
        'issuer_snapshot' => ['display_name' => 'Loja'],
    ]);

    $this->actingAs($user)
        ->delete(route('transactions.destroy', $trans))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('transactions', [
        'id' => $trans->id,
        'deleted_at' => null,
    ]);

    // Cancelar o recibo libera a exclusão
    $receipt->update(['status' => 'canceled', 'canceled_at' => now()]);

    $this->actingAs($user)
        ->delete(route('transactions.destroy', $trans))
        ->assertRedirect(route('transactions.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('transactions', ['id' => $trans->id]);
});

test('transaction enforces RN-02 max amount, RN-03 no future date, RN-05 no canceled appointment and RN-07 client consistency', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $clientA = Client::create(['user_id' => $user->id, 'name' => 'Cliente Real']);
    $clientB = Client::create(['user_id' => $user->id, 'name' => 'Outro Cliente']);

    $canceledAppt = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'service_description' => 'Atendimento Cancelado',
        'service_at' => now(),
        'status' => AppointmentStatus::Canceled,
    ]);

    $activeAppt = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $clientA->id,
        'service_description' => 'Atendimento Ativo',
        'service_at' => now(),
        'status' => AppointmentStatus::Completed,
    ]);

    // RN-02 + RN-03: valor acima do teto e data futura
    $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '10000000.00',
        'description' => 'Valor exorbitante',
        'occurred_on' => now()->addDay()->format('Y-m-d'),
        'payment_method' => 'pix',
    ])->assertSessionHasErrors(['amount', 'occurred_on']);

    // RN-05: vínculo com atendimento cancelado
    $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '100.00',
        'description' => 'Atendimento cancelado',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'appointment_id' => $canceledAppt->id,
    ])->assertSessionHasErrors(['appointment_id']);

    // RN-07: enviar client_id diferente do atendimento deve gravar o client_id do atendimento
    $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '150.00',
        'description' => 'Recebimento consistente',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'appointment_id' => $activeAppt->id,
        'client_id' => $clientB->id,
    ])->assertRedirect(route('transactions.index'));

    $tx = Transaction::where('appointment_id', $activeAppt->id)->first();
    expect($tx)->not->toBeNull()
        ->and($tx->client_id)->toBe($clientA->id);
});

test('transaction rejects category of mismatched type and create page provides all categories', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $incomeCategory = $user->categories()->where('type', 'income')->first();
    $expenseCategory = $user->categories()->where('type', 'expense')->first();

    $this->actingAs($user)
        ->get(route('transactions.create', ['type' => 'income']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Transactions/Create')
            ->has('categories', $user->categories()->count())
        );

    $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'expense',
        'amount' => '100,00',
        'description' => 'Categoria invertida',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'category_id' => $incomeCategory->id,
    ])->assertSessionHasErrors(['category_id']);

    $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'expense',
        'amount' => '100,00',
        'description' => 'Categoria correta',
        'occurred_on' => now()->format('Y-m-d'),
        'payment_method' => 'pix',
        'category_id' => $expenseCategory->id,
    ])->assertRedirect(route('transactions.index'));
});

test('user can edit transaction without active receipt and RN-22 locks fields when receipt is active', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $otherUser = User::factory()->withDefaultCategories()->create();
    $incomeCategory = $user->categories()->where('type', 'income')->first();

    $tx = Transaction::create([
        'user_id' => $user->id,
        'category_id' => $incomeCategory->id,
        'type' => 'income',
        'amount_cents' => 20000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço Inicial',
        'payment_method' => PaymentMethod::Pix,
    ]);

    // Cross-tenant edit/update -> 404
    $this->actingAs($otherUser)->get(route('transactions.edit', $tx))->assertNotFound();
    $this->actingAs($otherUser)->put(route('transactions.update', $tx), [
        'type' => 'income',
        'amount' => '999.00',
        'occurred_on' => now()->toDateString(),
        'description' => 'Hack',
        'payment_method' => 'pix',
    ])->assertNotFound();

    // Edit without active receipt succeeds
    $this->actingAs($user)->get(route('transactions.edit', $tx))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Transactions/Edit')
            ->where('hasActiveReceipt', false)
        );

    $this->actingAs($user)->put(route('transactions.update', $tx), [
        'type' => 'income',
        'amount' => '250.00',
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço Atualizado',
        'payment_method' => 'cash',
        'category_id' => $incomeCategory->id,
        'notes' => 'Nota atualizada',
    ])->assertRedirect(route('transactions.index'));

    $tx->refresh();
    expect($tx->amount_cents)->toBe(25000)
        ->and($tx->description)->toBe('Serviço Atualizado')
        ->and($tx->payment_method)->toBe(PaymentMethod::Cash);

    // Issue active receipt -> locked fields cannot be changed (RN-22)
    $receipt = Receipt::create([
        'user_id' => $user->id,
        'transaction_id' => $tx->id,
        'code' => Str::uuid()->toString(),
        'year' => (int) now()->format('Y'),
        'number' => 1,
        'status' => 'issued',
        'issued_at' => now(),
        'amount_cents' => 25000,
        'payment_method' => 'cash',
        'description' => 'Serviço Atualizado',
        'payer_name' => 'Cliente',
        'issuer_snapshot' => ['display_name' => 'Loja'],
    ]);

    $this->actingAs($user)->put(route('transactions.update', $tx), [
        'type' => 'income',
        'amount' => '300.00',
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço Atualizado',
        'payment_method' => 'cash',
        'category_id' => $incomeCategory->id,
    ])->assertSessionHas('error');

    expect($tx->refresh()->amount_cents)->toBe(25000);

    // Cancel receipt -> full edit is allowed again
    $receipt->update(['status' => 'canceled', 'canceled_at' => now()]);

    $this->actingAs($user)->put(route('transactions.update', $tx), [
        'type' => 'income',
        'amount' => '300.00',
        'occurred_on' => now()->toDateString(),
        'description' => 'Serviço Pós Cancelamento',
        'payment_method' => 'pix',
        'category_id' => $incomeCategory->id,
    ])->assertRedirect(route('transactions.index'));

    expect($tx->refresh()->amount_cents)->toBe(30000);
});
