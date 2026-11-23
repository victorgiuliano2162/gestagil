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

test('restoring backup cannot hijack or overwrite another user receipt by code', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $clientA = Client::factory()->create(['user_id' => $userA->id]);
    $txA = Transaction::create([
        'user_id' => $userA->id,
        'client_id' => $clientA->id,
        'type' => 'income',
        'amount_cents' => 50000,
        'occurred_on' => now()->format('Y-m-d'),
        'description' => 'Serviço Original A',
    ]);

    $stolenCode = (string) Str::uuid();
    $receiptA = \App\Models\Receipt::create([
        'user_id' => $userA->id,
        'transaction_id' => $txA->id,
        'client_id' => $clientA->id,
        'year' => (int) now()->format('Y'),
        'number' => 1,
        'code' => $stolenCode,
        'status' => 'issued',
        'issued_at' => now(),
        'amount_cents' => 50000,
        'payment_method' => 'pix',
        'description' => 'Recibo Original A',
        'payer_name' => 'Pagador Original A',
        'issuer_snapshot' => ['display_name' => 'Empresa A'],
    ]);

    $clientBUuid = (string) Str::uuid();
    $txBUuid = (string) Str::uuid();

    foreach (['merge', 'replace'] as $mode) {
        $payload = [
            'gestagil_backup_version' => 1,
            'data' => [
                'clients' => [
                    ['uuid' => $clientBUuid, 'name' => 'Cliente B'],
                ],
                'appointments' => [],
                'transactions' => [
                    [
                        'uuid' => $txBUuid,
                        'client_uuid' => $clientBUuid,
                        'type' => 'income',
                        'amount_cents' => 100,
                        'occurred_on' => now()->format('Y-m-d'),
                        'description' => 'Lançamento B',
                        'payment_method' => 'pix',
                    ],
                ],
                'receipts' => [
                    [
                        'code' => $stolenCode,
                        'transaction_uuid' => $txBUuid,
                        'client_uuid' => $clientBUuid,
                        'year' => (int) now()->format('Y'),
                        'number' => 1,
                        'status' => 'issued',
                        'issued_at' => now()->toIso8601String(),
                        'amount_cents' => 100,
                        'payment_method' => 'cash',
                        'description' => 'Tentativa de Sequestro',
                        'payer_name' => 'Atacante B',
                        'issuer_snapshot' => ['display_name' => 'Empresa B'],
                    ],
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent(
            'hijack_backup.json',
            json_encode($payload)
        );

        $this->actingAs($userB)->post(route('backup.restore'), [
            'backup_file' => $file,
            'mode' => $mode,
            'confirmed_overwrite' => $mode === 'replace',
        ])->assertRedirect(route('backup.index'));

        $receiptA->refresh();
        expect($receiptA->user_id)->toBe($userA->id)
            ->and($receiptA->payer_name)->toBe('Pagador Original A')
            ->and($receiptA->amount_cents)->toBe(50000);

        $importedB = \App\Models\Receipt::withoutGlobalScopes()
            ->where('user_id', $userB->id)
            ->first();
        expect($importedB)->not->toBeNull()
            ->and($importedB->code)->not->toBe($stolenCode);
    }
});

test('export and replace restore preserve complete business profile and handle soft-deleted records', function () {
    $user = User::factory()->create();
    \App\Models\BusinessProfile::create([
        'user_id' => $user->id,
        'display_name' => 'Oficina Modelo',
        'store_name' => 'Auto Center Modelo',
        'activity_description' => 'Mecânica e Elétrica',
        'document_type' => 'cnpj',
        'document_number' => '12.345.678/0001-95',
        'phone' => '81999990000',
        'postal_code' => '50000-000',
        'street' => 'Rua das Flores',
        'number' => '123',
        'complement' => 'Galpão B',
        'neighborhood' => 'Centro',
        'city' => 'Recife',
        'state' => 'PE',
    ]);

    $client = Client::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cliente Soft Deleted',
    ]);
    $clientUuid = $client->uuid;

    $response = $this->actingAs($user)->get(route('backup.export'));
    $response->assertOk();

    $exportedJson = $response->streamedContent();
    $exported = json_decode($exportedJson, true);

    expect($exported['data']['business_profile'])->toMatchArray([
        'display_name' => 'Oficina Modelo',
        'store_name' => 'Auto Center Modelo',
        'activity_description' => 'Mecânica e Elétrica',
        'street' => 'Rua das Flores',
        'number' => '123',
        'complement' => 'Galpão B',
        'neighborhood' => 'Centro',
        'city' => 'Recife',
        'state' => 'PE',
    ]);

    // Soft-delete client before replace restore with same UUID
    $client->delete();

    $file = UploadedFile::fake()->createWithContent('backup.json', $exportedJson);

    $this->actingAs($user)->post(route('backup.restore'), [
        'backup_file' => $file,
        'mode' => 'replace',
        'confirmed_overwrite' => true,
    ])->assertRedirect(route('backup.index'))
        ->assertSessionHas('success');

    $restoredClient = Client::where('user_id', $user->id)->where('uuid', $clientUuid)->first();
    expect($restoredClient)->not->toBeNull()
        ->and($restoredClient->name)->toBe('Cliente Soft Deleted');

    $restoredProfile = \App\Models\BusinessProfile::where('user_id', $user->id)->first();
    expect($restoredProfile->street)->toBe('Rua das Flores')
        ->and($restoredProfile->neighborhood)->toBe('Centro')
        ->and($restoredProfile->complement)->toBe('Galpão B')
        ->and($restoredProfile->activity_description)->toBe('Mecânica e Elétrica');
});
