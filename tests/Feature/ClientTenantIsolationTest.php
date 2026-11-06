<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view their clients', function () {
    $user = User::factory()->create();

    Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Teste 1',
        'phone_e164' => '81999990001',
    ]);

    $response = $this->actingAs($user)->get(route('clients.index'));

    $response->assertOk();
    $response->assertSee('Cliente Teste 1');
});

test('user cannot view or access another users clients (IDOR prevention)', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $clientA = Client::create([
        'user_id' => $userA->id,
        'name' => 'Cliente Secreto do User A',
    ]);

    // User B tries to view client A in index
    $responseIndex = $this->actingAs($userB)->get(route('clients.index'));
    $responseIndex->assertOk();
    $responseIndex->assertDontSee('Cliente Secreto do User A');

    // User B tries to directly access client A via show (should be 404 due to UserScope)
    $responseShow = $this->actingAs($userB)->get(route('clients.show', $clientA));
    $responseShow->assertNotFound();

    // User B tries to update client A
    $responseUpdate = $this->actingAs($userB)->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->put(route('clients.update', $clientA), [
        'name' => 'Hacked Name',
    ]);
    $responseUpdate->assertNotFound();

    // User B tries to delete client A
    $responseDelete = $this->actingAs($userB)->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->delete(route('clients.destroy', $clientA));
    $responseDelete->assertNotFound();
});
