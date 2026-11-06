<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('user can delete account with full relational graph in reverse topological order', function () {
    $user = User::factory()->withBusinessProfile()->withDefaultCategories()->create();

    $client = \App\Models\Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Vinculado',
    ]);

    $appointment = \App\Models\Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Serviço',
        'service_at' => now(),
        'status' => \App\Enums\AppointmentStatus::Completed,
    ]);

    $transaction = \App\Models\Transaction::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'appointment_id' => $appointment->id,
        'type' => 'income',
        'amount_cents' => 5000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Venda',
        'payment_method' => \App\Enums\PaymentMethod::Pix,
    ]);

    \App\Models\Receipt::create([
        'user_id' => $user->id,
        'transaction_id' => $transaction->id,
        'client_id' => $client->id,
        'code' => \Illuminate\Support\Str::uuid()->toString(),
        'year' => (int) now()->format('Y'),
        'number' => 1,
        'status' => 'issued',
        'issued_at' => now(),
        'amount_cents' => 5000,
        'payment_method' => 'pix',
        'description' => 'Recibo',
        'payer_name' => 'Cliente Vinculado',
        'issuer_snapshot' => ['store_name' => 'Loja'],
    ]);

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    $this->assertDatabaseMissing('receipts', ['transaction_id' => $transaction->id]);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
