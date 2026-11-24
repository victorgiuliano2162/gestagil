<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Vinculado',
    ]);

    $appointment = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Serviço',
        'service_at' => now(),
        'status' => AppointmentStatus::Completed,
    ]);

    $transaction = Transaction::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'appointment_id' => $appointment->id,
        'type' => 'income',
        'amount_cents' => 5000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Venda',
        'payment_method' => PaymentMethod::Pix,
    ]);

    Receipt::create([
        'user_id' => $user->id,
        'transaction_id' => $transaction->id,
        'client_id' => $client->id,
        'code' => Str::uuid()->toString(),
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

test('deleting account removes business profile logo from local disk', function () {
    Storage::fake('local');

    $user = User::factory()->withBusinessProfile()->create();
    $logoPath = 'logos/user-logo.png';
    Storage::disk('local')->put($logoPath, 'fake-image-content');
    $user->businessProfile->update(['logo_path' => $logoPath]);

    Storage::disk('local')->assertExists($logoPath);

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    Storage::disk('local')->assertMissing($logoPath);
});

test('user can update their preferred color theme', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/profile/theme', ['theme' => 'dark'])
        ->assertOk()
        ->assertJson(['theme' => 'dark']);

    expect($user->refresh()->theme)->toBe('dark');

    $this->actingAs($user)
        ->patchJson('/profile/theme', ['theme' => 'light'])
        ->assertOk()
        ->assertJson(['theme' => 'light']);

    expect($user->refresh()->theme)->toBe('light');

    $this->actingAs($user)
        ->patchJson('/profile/theme', ['theme' => 'system'])
        ->assertOk()
        ->assertJson(['theme' => 'system']);

    expect($user->refresh()->theme)->toBe('system');
});

test('invalid color theme is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/profile/theme', ['theme' => 'neon'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('theme');
});
