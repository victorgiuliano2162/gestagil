<?php

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

test('authenticated user can view backup index page with stats', function () {
    $user = User::factory()->create();

    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente Teste',
    ]);

    Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Serviço Teste',
        'service_at' => now(),
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'type' => 'income',
        'amount_cents' => 10000,
        'occurred_on' => now()->format('Y-m-d'),
        'description' => 'Lançamento Teste',
    ]);

    $response = $this->actingAs($user)->get(route('backup.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backup/Index')
            ->has('stats', fn ($stats) => $stats
                ->where('total_clients', 1)
                ->where('total_appointments', 1)
                ->where('total_transactions', 1)
                ->etc()
            )
        );
});

test('user can export their data as a valid structured json backup', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Cliente do Usuario Principal',
    ]);

    $otherClient = Client::create([
        'user_id' => $otherUser->id,
        'name' => 'Cliente de Outro Usuario',
    ]);

    $appointment = Appointment::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'service_description' => 'Serviço de Teste',
        'service_at' => now(),
    ]);

    $transaction = Transaction::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'appointment_id' => $appointment->id,
        'description' => 'Pagamento de Serviço',
        'type' => 'income',
        'amount_cents' => 5000,
        'occurred_on' => now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user)->get(route('backup.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/json');

    $content = $response->streamedContent();
    $data = json_decode($content, true);

    expect($data)->toHaveKey('gestagil_backup_version', 1)
        ->and($data['metadata']['total_clients'])->toBe(1)
        ->and($data['data']['clients'][0]['name'])->toBe('Cliente do Usuario Principal')
        ->and($data['data']['clients'][0]['uuid'])->toBe($client->uuid)
        ->and($data['data']['appointments'][0]['client_uuid'])->toBe($client->uuid)
        ->and($data['data']['transactions'][0]['appointment_uuid'])->toBe($appointment->uuid);

    // Multi-tenant check: Other user's client is NOT in export
    $exportedNames = array_column($data['data']['clients'], 'name');
    expect($exportedNames)->not->toContain('Cliente de Outro Usuario');
});

test('restoring backup in merge mode adds new records without duplicating existing ones', function () {
    $user = User::factory()->create();

    $existingClient = Client::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cliente Existente',
        'document_number' => '11122233344',
    ]);

    $newClientUuid = (string) Str::uuid();
    $newApptUuid = (string) Str::uuid();

    $backupPayload = [
        'gestagil_backup_version' => 1,
        'app_version' => '1.0.0',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'business_profile' => null,
            'categories' => [],
            'clients' => [
                // Existing client
                [
                    'uuid' => $existingClient->uuid,
                    'name' => 'Cliente Existente Atualizado',
                    'document_number' => '11122233344',
                ],
                // Brand new client
                [
                    'uuid' => $newClientUuid,
                    'name' => 'Novo Cliente Importado',
                    'document_number' => '99988877766',
                ],
            ],
            'appointments' => [
                [
                    'uuid' => $newApptUuid,
                    'client_uuid' => $newClientUuid,
                    'service_description' => 'Atendimento do Novo Cliente',
                    'service_at' => now()->toIso8601String(),
                    'status' => 'in_progress',
                ],
            ],
            'transactions' => [],
            'receipts' => [],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'gestagil_backup.json',
        json_encode($backupPayload)
    );

    $response = $this->actingAs($user)->post(route('backup.restore'), [
        'backup_file' => $file,
        'mode' => 'merge',
    ]);

    $response->assertRedirect(route('backup.index'));
    $response->assertSessionHas('success');

    // Verify existing client was updated, not duplicated
    expect(Client::where('user_id', $user->id)->count())->toBe(2);

    $existingClient->refresh();
    expect($existingClient->name)->toBe('Cliente Existente Atualizado');

    // Verify new client and appointment were created
    $importedClient = Client::where('user_id', $user->id)->where('uuid', $newClientUuid)->first();
    expect($importedClient)->not->toBeNull()
        ->and($importedClient->name)->toBe('Novo Cliente Importado');

    $importedAppt = Appointment::where('user_id', $user->id)->where('uuid', $newApptUuid)->first();
    expect($importedAppt)->not->toBeNull()
        ->and($importedAppt->client_id)->toBe($importedClient->id);
});

test('restoring backup in replace mode fails without double check confirmation', function () {
    $user = User::factory()->create();

    $backupPayload = [
        'gestagil_backup_version' => 1,
        'data' => [
            'clients' => [],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'gestagil_backup.json',
        json_encode($backupPayload)
    );

    $response = $this->actingAs($user)->post(route('backup.restore'), [
        'backup_file' => $file,
        'mode' => 'replace',
        'confirmed_overwrite' => false,
    ]);

    $response->assertSessionHasErrors(['confirmed_overwrite']);
});

test('restoring backup in replace mode replaces user data cleanly when double check is confirmed', function () {
    $user = User::factory()->create();

    // Create current data that should be wiped
    $oldClient = Client::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cliente Antigo Que Sera Apagado',
    ]);

    $replacementClientUuid = (string) Str::uuid();

    $backupPayload = [
        'gestagil_backup_version' => 1,
        'app_version' => '1.0.0',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'business_profile' => null,
            'categories' => [],
            'clients' => [
                [
                    'uuid' => $replacementClientUuid,
                    'name' => 'Cliente do Arquivo Sobrescrito',
                ],
            ],
            'appointments' => [],
            'transactions' => [],
            'receipts' => [],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'gestagil_backup.json',
        json_encode($backupPayload)
    );

    $response = $this->actingAs($user)->post(route('backup.restore'), [
        'backup_file' => $file,
        'mode' => 'replace',
        'confirmed_overwrite' => true,
    ]);

    $response->assertRedirect(route('backup.index'));
    $response->assertSessionHas('success');

    // Verify old client is gone and only the replaced client exists
    expect(Client::where('user_id', $user->id)->count())->toBe(1);
    expect(Client::where('user_id', $user->id)->first()->name)->toBe('Cliente do Arquivo Sobrescrito');
});

test('restore in replace mode does not touch data of other users (tenant isolation)', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherClient = Client::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Cliente Intocavel do Outro Usuario',
    ]);

    $backupPayload = [
        'gestagil_backup_version' => 1,
        'data' => [
            'clients' => [
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Cliente Novo Usuario 1',
                ],
            ],
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'gestagil_backup.json',
        json_encode($backupPayload)
    );

    $this->actingAs($user)->post(route('backup.restore'), [
        'backup_file' => $file,
        'mode' => 'replace',
        'confirmed_overwrite' => true,
    ]);

    // Other user's client must remain completely intact
    $reloadedOther = Client::withoutGlobalScopes()->find($otherClient->id);
    expect($reloadedOther)->not->toBeNull()
        ->and($reloadedOther->name)->toBe('Cliente Intocavel do Outro Usuario');
});
