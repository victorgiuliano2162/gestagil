<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('system rejects logo larger than 2MB with clear error message', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    // Create a 2.5MB fake image (2500 KB)
    $heavyFile = UploadedFile::fake()->create('heavy_logo.png', 2500, 'image/png');

    $response = $this->actingAs($user)->post(route('business-profile.update'), [
        'store_name' => 'Loja Teste',
        'display_name' => 'Loja Teste LTDA',
        'logo' => $heavyFile,
    ]);

    $response->assertSessionHasErrors([
        'logo' => 'O logotipo não pode ultrapassar 2MB de tamanho.',
    ]);
});
