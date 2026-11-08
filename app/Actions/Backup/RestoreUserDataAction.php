<?php

namespace App\Actions\Backup;

use App\Models\Appointment;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RestoreUserDataAction
{
    public function __construct(
        protected ExportUserDataAction $exportAction
    ) {}

    /**
     * @param  array  $payload  Decoded backup JSON
     * @param  string  $mode  'merge' | 'replace'
     * @param  bool  $confirmedOverwrite  Explicit double-check flag required for replace mode
     * @return array Summary of restored records
     */
    public function execute(User $user, array $payload, string $mode = 'merge', bool $confirmedOverwrite = false): array
    {
        if (! isset($payload['gestagil_backup_version']) || ! isset($payload['data'])) {
            throw new \InvalidArgumentException('Arquivo de backup inválido ou incompatível.');
        }

        if ($mode === 'replace') {
            if (! $confirmedOverwrite) {
                throw new \InvalidArgumentException('A confirmação explícita (double check) é obrigatória para sobrescrever dados.');
            }

            // Save pre-restore safety snapshot
            $this->saveSafetySnapshot($user);
        }

        return DB::transaction(function () use ($user, $payload, $mode) {
            if ($mode === 'replace') {
                $this->clearUserData($user);
            }

            $data = $payload['data'];

            // 1. Business Profile
            if (! empty($data['business_profile'])) {
                BusinessProfile::withoutGlobalScopes()->updateOrCreate(
                    ['user_id' => $user->id],
                    $data['business_profile']
                );
            }

            // 2. Categories
            $categoryMap = []; // [type:name_key => id]
            if (! empty($data['categories']) && is_array($data['categories'])) {
                foreach ($data['categories'] as $cat) {
                    $category = TransactionCategory::withoutGlobalScopes()->firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'type' => $cat['type'],
                            'name_key' => $cat['name_key'],
                        ],
                        [
                            'name' => $cat['name'],
                            'color' => $cat['color'] ?? null,
                        ]
                    );
                    $categoryMap[$cat['type'].':'.$cat['name_key']] = $category->id;
                }
            }

            // Also load existing user categories into map
            $existingCategories = TransactionCategory::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->get();
            foreach ($existingCategories as $ec) {
                $categoryMap[$ec->type.':'.$ec->name_key] = $ec->id;
            }

            // 3. Clients
            $clientUuidMap = []; // [uuid => id]
            $clientsCount = 0;
            if (! empty($data['clients']) && is_array($data['clients'])) {
                foreach ($data['clients'] as $c) {
                    $uuid = $c['uuid'] ?? (string) Str::uuid();

                    $client = Client::withoutGlobalScopes()
                        ->where('user_id', $user->id)
                        ->where(function ($query) use ($uuid, $c) {
                            $query->where('uuid', $uuid);
                            if (! empty($c['document_number'])) {
                                $query->orWhere('document_number', $c['document_number']);
                            }
                        })
                        ->first();

                    $attributes = [
                        'name' => $c['name'],
                        'search_name' => $c['search_name'] ?? Str::lower(Str::ascii($c['name'])),
                        'phone_e164' => $c['phone_e164'] ?? null,
                        'document_type' => $c['document_type'] ?? null,
                        'document_number' => $c['document_number'] ?? null,
                        'email' => $c['email'] ?? null,
                        'notes' => $c['notes'] ?? null,
                    ];

                    if ($client) {
                        $client->update($attributes);
                    } else {
                        $attributes['user_id'] = $user->id;
                        $attributes['uuid'] = $uuid;
                        $client = Client::withoutGlobalScopes()->create($attributes);
                        $clientsCount++;
                    }

                    $clientUuidMap[$uuid] = $client->id;
                }
            }

            // Map any existing clients by uuid
            $allClients = Client::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->pluck('id', 'uuid')
                ->toArray();
            $clientUuidMap = array_merge($allClients, $clientUuidMap);

            // 4. Appointments
            $appointmentUuidMap = []; // [uuid => id]
            $appointmentsCount = 0;
            if (! empty($data['appointments']) && is_array($data['appointments'])) {
                foreach ($data['appointments'] as $a) {
                    $uuid = $a['uuid'] ?? (string) Str::uuid();
                    $clientId = null;
                    if (! empty($a['client_uuid']) && isset($clientUuidMap[$a['client_uuid']])) {
                        $clientId = $clientUuidMap[$a['client_uuid']];
                    }

                    if (! $clientId) {
                        // Needs a valid client; if not mapped, pick the first client or skip
                        $clientId = Client::withoutGlobalScopes()->where('user_id', $user->id)->value('id');
                    }

                    if (! $clientId) {
                        continue;
                    }

                    $appointment = Appointment::withoutGlobalScopes()
                        ->where('user_id', $user->id)
                        ->where('uuid', $uuid)
                        ->first();

                    $attributes = [
                        'client_id' => $clientId,
                        'service_description' => $a['service_description'],
                        'service_at' => $a['service_at'],
                        'status' => $a['status'] ?? 'in_progress',
                        'technical_notes' => $a['technical_notes'] ?? null,
                        'expected_amount_cents' => $a['expected_amount_cents'] ?? null,
                        'completed_at' => $a['completed_at'] ?? null,
                        'canceled_at' => $a['canceled_at'] ?? null,
                        'cancel_reason' => $a['cancel_reason'] ?? null,
                    ];

                    if ($appointment) {
                        $appointment->update($attributes);
                    } else {
                        $attributes['user_id'] = $user->id;
                        $attributes['uuid'] = $uuid;
                        $appointment = Appointment::withoutGlobalScopes()->create($attributes);
                        $appointmentsCount++;
                    }

                    $appointmentUuidMap[$uuid] = $appointment->id;
                }
            }

            $allAppointments = Appointment::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->pluck('id', 'uuid')
                ->toArray();
            $appointmentUuidMap = array_merge($allAppointments, $appointmentUuidMap);

            // 5. Transactions
            $transactionUuidMap = []; // [uuid => id]
            $transactionsCount = 0;
            if (! empty($data['transactions']) && is_array($data['transactions'])) {
                foreach ($data['transactions'] as $t) {
                    $uuid = $t['uuid'] ?? (string) Str::uuid();
                    $clientId = ! empty($t['client_uuid']) ? ($clientUuidMap[$t['client_uuid']] ?? null) : null;
                    $appointmentId = ! empty($t['appointment_uuid']) ? ($appointmentUuidMap[$t['appointment_uuid']] ?? null) : null;

                    $categoryId = null;
                    if (! empty($t['category_type']) && ! empty($t['category_name_key'])) {
                        $categoryId = $categoryMap[$t['category_type'].':'.$t['category_name_key']] ?? null;
                    }

                    $transaction = Transaction::withoutGlobalScopes()
                        ->where('user_id', $user->id)
                        ->where('uuid', $uuid)
                        ->first();

                    $attributes = [
                        'type' => $t['type'],
                        'amount_cents' => $t['amount_cents'],
                        'occurred_on' => $t['occurred_on'],
                        'description' => $t['description'],
                        'category_id' => $categoryId,
                        'payment_method' => $t['payment_method'] ?? 'pix',
                        'appointment_id' => $appointmentId,
                        'client_id' => $clientId,
                        'notes' => $t['notes'] ?? null,
                    ];

                    if ($transaction) {
                        $transaction->update($attributes);
                    } else {
                        $attributes['user_id'] = $user->id;
                        $attributes['uuid'] = $uuid;
                        $transaction = Transaction::withoutGlobalScopes()->create($attributes);
                        $transactionsCount++;
                    }

                    $transactionUuidMap[$uuid] = $transaction->id;
                }
            }

            $allTransactions = Transaction::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->pluck('id', 'uuid')
                ->toArray();
            $transactionUuidMap = array_merge($allTransactions, $transactionUuidMap);

            // 6. Receipts
            $receiptsCount = 0;
            if (! empty($data['receipts']) && is_array($data['receipts'])) {
                foreach ($data['receipts'] as $r) {
                    $code = $r['code'] ?? (string) Str::uuid();
                    $transactionId = ! empty($r['transaction_uuid']) ? ($transactionUuidMap[$r['transaction_uuid']] ?? null) : null;
                    $clientId = ! empty($r['client_uuid']) ? ($clientUuidMap[$r['client_uuid']] ?? null) : null;

                    if (! $transactionId) {
                        continue;
                    }

                    $receipt = Receipt::withoutGlobalScopes()
                        ->where('code', $code)
                        ->first();

                    $attributes = [
                        'user_id' => $user->id,
                        'transaction_id' => $transactionId,
                        'client_id' => $clientId,
                        'year' => $r['year'],
                        'number' => $r['number'],
                        'status' => $r['status'] ?? 'issued',
                        'issued_at' => $r['issued_at'],
                        'amount_cents' => $r['amount_cents'],
                        'payment_method' => $r['payment_method'] ?? 'pix',
                        'description' => $r['description'],
                        'payer_name' => $r['payer_name'],
                        'payer_document' => $r['payer_document'] ?? null,
                        'issuer_snapshot' => $r['issuer_snapshot'] ?? [],
                        'canceled_at' => $r['canceled_at'] ?? null,
                        'cancel_reason' => $r['cancel_reason'] ?? null,
                    ];

                    if ($receipt) {
                        $receipt->update($attributes);
                    } else {
                        // Check uniqueness of year and number per user to avoid collision
                        $existingNum = Receipt::withoutGlobalScopes()
                            ->where('user_id', $user->id)
                            ->where('year', $attributes['year'])
                            ->where('number', $attributes['number'])
                            ->exists();

                        if ($existingNum) {
                            $maxNum = (int) Receipt::withoutGlobalScopes()
                                ->where('user_id', $user->id)
                                ->where('year', $attributes['year'])
                                ->max('number');
                            $attributes['number'] = $maxNum + 1;
                        }

                        $attributes['code'] = $code;
                        Receipt::withoutGlobalScopes()->create($attributes);
                        $receiptsCount++;
                    }
                }
            }

            return [
                'mode' => $mode,
                'clients_imported' => $clientsCount,
                'appointments_imported' => $appointmentsCount,
                'transactions_imported' => $transactionsCount,
                'receipts_imported' => $receiptsCount,
            ];
        });
    }

    protected function saveSafetySnapshot(User $user): void
    {
        $snapshot = $this->exportAction->execute($user);
        $dir = storage_path('app/backups');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filename = sprintf('pre_restore_user_%d_%s.json', $user->id, now()->format('Ymd_His'));
        File::put($dir.'/'.$filename, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function clearUserData(User $user): void
    {
        Receipt::withoutGlobalScopes()->where('user_id', $user->id)->forceDelete();
        Transaction::withoutGlobalScopes()->where('user_id', $user->id)->forceDelete();
        Appointment::withoutGlobalScopes()->where('user_id', $user->id)->forceDelete();
        Client::withoutGlobalScopes()->where('user_id', $user->id)->forceDelete();
    }
}
