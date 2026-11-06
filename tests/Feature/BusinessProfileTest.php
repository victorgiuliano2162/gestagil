<?php

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('authenticated user can view and update their business profile commercial data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('business-profile.edit'));
    $response->assertOk();

    $updateResponse = $this->actingAs($user)->post(route('business-profile.update'), [
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

test('user can upload store logo and stream it safely', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->actingAs($user)->post(route('business-profile.update'), [
        'store_name' => 'Padaria Pão Quente',
        'display_name' => 'Padaria Pão Quente LTDA',
        'logo' => $file,
    ]);

    $response->assertSessionHas('status', 'profile-updated');

    $profile = BusinessProfile::where('user_id', $user->id)->first();
    expect($profile->logo_path)->not->toBeNull();
    Storage::disk('local')->assertExists($profile->logo_path);

    $logoResponse = $this->actingAs($user)->get(route('business-profile.logo'));
    $logoResponse->assertOk();
});
