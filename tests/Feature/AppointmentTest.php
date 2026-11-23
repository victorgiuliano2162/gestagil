<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;

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

    \App\Models\Transaction::create([
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
