<?php

namespace App\Actions\Backup;

use App\Models\Appointment;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;

class ExportUserDataAction
{
    public function execute(User $user): array
    {
        $profile = BusinessProfile::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        $categories = TransactionCategory::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($c) => [
                'type' => $c->type,
                'name' => $c->name,
                'name_key' => $c->name_key,
                'color' => $c->color,
            ])
            ->toArray();

        $clients = Client::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->get();

        $clientsMap = $clients->pluck('uuid', 'id')->toArray();

        $clientsData = $clients->map(fn ($c) => [
            'uuid' => $c->uuid,
            'name' => $c->name,
            'search_name' => $c->search_name,
            'phone_e164' => $c->phone_e164,
            'document_type' => $c->document_type,
            'document_number' => $c->document_number,
            'email' => $c->email,
            'notes' => $c->notes,
            'created_at' => $c->created_at?->toIso8601String(),
            'updated_at' => $c->updated_at?->toIso8601String(),
        ])->toArray();

        $appointments = Appointment::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->get();

        $appointmentsMap = $appointments->pluck('uuid', 'id')->toArray();

        $appointmentsData = $appointments->map(fn ($a) => [
            'uuid' => $a->uuid,
            'client_uuid' => $clientsMap[$a->client_id] ?? null,
            'service_description' => $a->service_description,
            'service_at' => $a->service_at?->toIso8601String(),
            'status' => is_string($a->status) ? $a->status : $a->status->value,
            'technical_notes' => $a->technical_notes,
            'expected_amount_cents' => $a->expected_amount_cents,
            'completed_at' => $a->completed_at?->toIso8601String(),
            'canceled_at' => $a->canceled_at?->toIso8601String(),
            'cancel_reason' => $a->cancel_reason,
            'created_at' => $a->created_at?->toIso8601String(),
            'updated_at' => $a->updated_at?->toIso8601String(),
        ])->toArray();

        $transactions = Transaction::withoutGlobalScopes()
            ->with('category')
            ->where('user_id', $user->id)
            ->get();

        $transactionsMap = $transactions->pluck('uuid', 'id')->toArray();

        $transactionsData = $transactions->map(fn ($t) => [
            'uuid' => $t->uuid,
            'type' => $t->type,
            'amount_cents' => $t->amount_cents,
            'occurred_on' => $t->occurred_on?->format('Y-m-d'),
            'description' => $t->description,
            'category_type' => $t->category?->type,
            'category_name_key' => $t->category?->name_key,
            'payment_method' => is_string($t->payment_method) ? $t->payment_method : $t->payment_method->value,
            'appointment_uuid' => $appointmentsMap[$t->appointment_id] ?? null,
            'client_uuid' => $clientsMap[$t->client_id] ?? null,
            'notes' => $t->notes,
            'created_at' => $t->created_at?->toIso8601String(),
            'updated_at' => $t->updated_at?->toIso8601String(),
        ])->toArray();

        $receipts = Receipt::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->get();

        $receiptsData = $receipts->map(fn ($r) => [
            'code' => $r->code,
            'transaction_uuid' => $transactionsMap[$r->transaction_id] ?? null,
            'client_uuid' => $clientsMap[$r->client_id] ?? null,
            'year' => $r->year,
            'number' => $r->number,
            'status' => $r->status,
            'issued_at' => $r->issued_at?->toIso8601String(),
            'amount_cents' => $r->amount_cents,
            'payment_method' => $r->payment_method,
            'description' => $r->description,
            'payer_name' => $r->payer_name,
            'payer_document' => $r->payer_document,
            'issuer_snapshot' => $r->issuer_snapshot,
            'canceled_at' => $r->canceled_at?->toIso8601String(),
            'cancel_reason' => $r->cancel_reason,
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
        ])->toArray();

        return [
            'gestagil_backup_version' => 1,
            'app_version' => '1.0.0',
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'metadata' => [
                'total_clients' => count($clientsData),
                'total_appointments' => count($appointmentsData),
                'total_transactions' => count($transactionsData),
                'total_receipts' => count($receiptsData),
            ],
            'data' => [
                'business_profile' => $profile ? [
                    'display_name' => $profile->display_name,
                    'trade_name' => $profile->trade_name,
                    'document_type' => $profile->document_type,
                    'document_number' => $profile->document_number,
                    'phone' => $profile->phone,
                    'email' => $profile->email,
                    'postal_code' => $profile->postal_code,
                    'address_line' => $profile->address_line,
                    'address_number' => $profile->address_number,
                    'district' => $profile->district,
                    'city' => $profile->city,
                    'state' => $profile->state,
                    'store_name' => $profile->store_name,
                    'activity_type' => $profile->activity_type,
                    'receipt_footer' => $profile->receipt_footer,
                ] : null,
                'categories' => $categories,
                'clients' => $clientsData,
                'appointments' => $appointmentsData,
                'transactions' => $transactionsData,
                'receipts' => $receiptsData,
            ],
        ];
    }
}
