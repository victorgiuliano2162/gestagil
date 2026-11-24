<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

test('authenticated user can create appointment and respect state transitions', function () {
    $user = User::factory()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Teste Atendimento',
    ]);

    // 1. Create appointment
    $createResponse = $this->actingAs($user)->post(route('appointments.store'), [
        'client_id' => $client->id,
        'service_description' => 'Troca de tela e bateria de celular',
        'service_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'expected_amount' => '250.00',
    ]);

    $createResponse->assertRedirect();
    $appointment = Appointment::where('user_id', $user->id)->first();
    expect($appointment)->not->toBeNull();
    expect($appointment->status)->toBe(AppointmentStatus::InProgress);
    expect($appointment->expected_amount_cents)->toBe(25000);

    // 2. Complete appointment
    $completeResponse = $this->actingAs($user)->patch(route('appointments.status', $appointment), [
        'status' => 'completed',
    ]);
    $completeResponse->assertSessionHasNoErrors();
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
    expect($appointment->completed_at)->not->toBeNull();

    // 3. Completed appointment cannot transition to canceled (RN-04)
    $invalidTransitionResponse = $this->actingAs($user)->patch(route('appointments.status', $appointment), [
        'status' => 'canceled',
    ]);
    $invalidTransitionResponse->assertSessionHasErrors('status');
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
});

test('user cannot view or modify appointments of another user (tenant isolation)', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $clientA = Client::create([
        'user_id' => $userA->id,
        'name' => 'Cliente do User A',
    ]);

    $appointmentA = Appointment::create([
        'user_id' => $userA->id,
        'client_id' => $clientA->id,
        'service_description' => 'Serviço Privado do User A',
        'service_at' => now(),
    ]);

    // User B tries to view appointment A via show -> 404
    $this->actingAs($userB)->get(route('appointments.show', $appointmentA))->assertNotFound();

    // User B tries to update appointment A -> 404
    $this->actingAs($userB)->put(route('appointments.update', $appointmentA), [
        'client_id' => $clientA->id,
        'service_description' => 'Tentativa de Hack',
        'service_at' => now()->format('Y-m-d H:i:s'),
    ])->assertNotFound();

    // User B tries to update status of appointment A -> 404
    $this->actingAs($userB)->patch(route('appointments.status', $appointmentA), [
        'status' => 'completed',
    ])->assertNotFound();
});

test('appointment cannot reference client belonging to another user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $clientA = Client::create([
        'user_id' => $userA->id,
        'name' => 'Cliente do User A',
    ]);

    $response = $this->actingAs($userB)->post(route('appointments.store'), [
        'client_id' => $clientA->id,
        'service_description' => 'Tentativa cross-tenant',
        'service_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors(['client_id']);
    expect(Appointment::withoutGlobalScopes()->where('user_id', $userB->id)->count())->toBe(0);
});

test('cannot delete appointment with linked transactions', function () {
    $user = User::factory()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Teste',
    ]);

    $apptWithTx = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Com lançamento',
        'service_at' => now(),
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'appointment_id' => $apptWithTx->id,
        'type' => 'income',
        'amount_cents' => 10000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Pagamento',
    ]);

    $this->actingAs($user)
        ->delete(route('appointments.destroy', $apptWithTx))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('appointments', [
        'id' => $apptWithTx->id,
        'deleted_at' => null,
    ]);

    $apptWithoutTx = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Sem lançamento',
        'service_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('appointments.destroy', $apptWithoutTx))
        ->assertRedirect(route('appointments.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('appointments', ['id' => $apptWithoutTx->id]);
});

test('appointment show page includes linked transactions and active receipts', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Show',
    ]);

    $appointment = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Instalação de som',
        'service_at' => now(),
        'status' => AppointmentStatus::Completed,
    ]);

    $tx = Transaction::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'appointment_id' => $appointment->id,
        'type' => 'income',
        'amount_cents' => 40000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Recebimento som',
        'payment_method' => PaymentMethod::Pix,
    ]);

    $receipt = Receipt::create([
        'user_id' => $user->id,
        'transaction_id' => $tx->id,
        'client_id' => $client->id,
        'code' => Str::uuid()->toString(),
        'year' => (int) now()->format('Y'),
        'number' => 1,
        'status' => 'issued',
        'issued_at' => now(),
        'amount_cents' => 40000,
        'payment_method' => 'pix',
        'description' => 'Recebimento som',
        'payer_name' => 'Cliente Show',
        'issuer_snapshot' => ['display_name' => 'Loja'],
    ]);

    $this->actingAs($user)
        ->get(route('appointments.show', $appointment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Appointments/Show')
            ->has('transactions', 1)
            ->where('transactions.0.id', $tx->id)
            ->where('transactions.0.active_receipt.id', $receipt->id)
        );
});

test('quick client creation, client show history and appointment filters work', function () {
    $user = User::factory()->withDefaultCategories()->create();

    // Quick store JSON
    $quickRes = $this->actingAs($user)->postJson(route('clients.quick-store'), [
        'name' => 'Cliente Rápido',
        'phone_e164' => '(81) 99999-1234',
        'document_type' => 'cpf',
        'document_number' => '123.456.789-01',
    ]);
    $quickRes->assertCreated()
        ->assertJsonPath('client.name', 'Cliente Rápido')
        ->assertJsonPath('client.phone_e164', '81999991234');
    $clientId = $quickRes->json('client.id');

    // Invalid phone / document regex rejected
    $this->actingAs($user)->postJson(route('clients.quick-store'), [
        'name' => 'Cliente Inválido',
        'phone_e164' => '123',
        'document_number' => '12345',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['phone_e164', 'document_number']);

    $otherClient = Client::create(['user_id' => $user->id, 'name' => 'Outro Cliente']);

    Appointment::create([
        'user_id' => $user->id,
        'client_id' => $clientId,
        'service_description' => 'Serviço do Rápido',
        'service_at' => now(),
        'status' => AppointmentStatus::Completed,
    ]);

    Appointment::create([
        'user_id' => $user->id,
        'client_id' => $otherClient->id,
        'service_description' => 'Serviço Antigo',
        'service_at' => now()->subDays(10),
        'status' => AppointmentStatus::InProgress,
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'client_id' => $clientId,
        'type' => 'income',
        'amount_cents' => 35000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Pagamento Rápido',
        'payment_method' => PaymentMethod::Pix,
    ]);

    // Client show includes history and total spent
    $this->actingAs($user)->get(route('clients.show', $clientId))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Show')
            ->where('stats.total_spent_cents', 35000)
            ->where('stats.appointments_count', 1)
            ->has('appointments', 1)
            ->has('transactions', 1)
        );

    // Appointment filters by client_id and date range
    $this->actingAs($user)->get(route('appointments.index', [
        'client_id' => $clientId,
        'date_from' => now()->subDay()->toDateString(),
        'date_to' => now()->addDay()->toDateString(),
    ]))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('appointments.data', 1)
            ->where('appointments.data.0.service_description', 'Serviço do Rápido')
        );
});

test('appointment can be created as already completed and paid in one step with optional redirect to receipt', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Balcão']);
    $incomeCategory = $user->categories()->where('type', 'income')->first();

    // Missing amount when is_completed_and_paid is true fails validation
    $this->actingAs($user)->post(route('appointments.store'), [
        'client_id' => $client->id,
        'service_description' => 'Venda rápida sem valor',
        'service_at' => now()->format('Y-m-d H:i:s'),
        'is_completed_and_paid' => true,
        'payment_method' => 'pix',
    ])->assertSessionHasErrors(['expected_amount']);

    // Completed and paid without immediate receipt -> redirects to appointments.show
    $response = $this->actingAs($user)->post(route('appointments.store'), [
        'client_id' => $client->id,
        'service_description' => 'Troca de cabo USB',
        'service_at' => now()->format('Y-m-d H:i:s'),
        'expected_amount' => '80,00',
        'is_completed_and_paid' => true,
        'payment_method' => 'pix',
        'category_id' => $incomeCategory->id,
    ]);

    $appt1 = Appointment::where('service_description', 'Troca de cabo USB')->first();
    expect($appt1)->not->toBeNull()
        ->and($appt1->status)->toBe(AppointmentStatus::Completed)
        ->and($appt1->completed_at)->not->toBeNull();
    $response->assertRedirect(route('appointments.show', $appt1));

    $tx1 = Transaction::where('appointment_id', $appt1->id)->first();
    expect($tx1)->not->toBeNull()
        ->and($tx1->type)->toBe('income')
        ->and($tx1->amount_cents)->toBe(8000)
        ->and($tx1->category_id)->toBe($incomeCategory->id)
        ->and($tx1->client_id)->toBe($client->id);

    // Completed and paid WITH issue_receipt -> redirects directly to receipts.create
    $response2 = $this->actingAs($user)->post(route('appointments.store'), [
        'client_id' => $client->id,
        'service_description' => 'Troca de bateria',
        'service_at' => now()->format('Y-m-d H:i:s'),
        'expected_amount' => '220,00',
        'is_completed_and_paid' => true,
        'payment_method' => 'card',
        'issue_receipt' => true,
    ]);

    $appt2 = Appointment::where('service_description', 'Troca de bateria')->first();
    $tx2 = Transaction::where('appointment_id', $appt2->id)->first();
    expect($tx2)->not->toBeNull()
        ->and($tx2->amount_cents)->toBe(22000);
    $response2->assertRedirect(route('receipts.create', ['transaction_id' => $tx2->id]));
});
