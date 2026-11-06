<?php

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can view dashboard with metrics and checklist', function () {
    $user = User::factory()->create();

    BusinessProfile::create([
        'user_id' => $user->id,
        'display_name' => $user->name,
        'store_name' => 'Empório Central',
        'activity_description' => 'Comércio de alimentos',
    ]);

    Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente VIP',
        'phone_e164' => '81988889999',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('metrics.store_name', 'Empório Central')
        ->where('metrics.activity_description', 'Comércio de alimentos')
        ->where('metrics.total_clients', 1)
        ->has('metrics.recent_clients', 1)
    );
});
