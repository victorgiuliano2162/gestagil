<?php

use App\Models\BusinessProfile;
use App\Models\User;

test('authenticated user can view and update their business profile commercial data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('business-profile.edit'));
    $response->assertOk();

    $updateResponse = $this->actingAs($user)->patch(route('business-profile.update'), [
        'store_name' => 'Mercadinho da Esquina',
        'display_name' => 'Comércio Silva LTDA',
        'activity_description' => 'Varejo de bebidas e secos & molhados',
        'document_type' => 'cnpj',
        'document_number' => '12.345.678/0001-90',
        'phone' => '81988887777',
        'city' => 'Recife',
        'state' => 'PE',
    ]);

    $updateResponse->assertSessionHas('status', 'profile-updated');

    $profile = BusinessProfile::where('user_id', $user->id)->first();
    expect($profile)->not->toBeNull();
    expect($profile->store_name)->toBe('Mercadinho da Esquina');
    expect($profile->activity_description)->toBe('Varejo de bebidas e secos & molhados');
    expect($profile->city)->toBe('Recife');
});
