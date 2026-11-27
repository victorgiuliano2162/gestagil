<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Usuário principal de testes com perfil comercial completo, categorias e clientes
        $mainUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Carlos Silva',
                'password' => Hash::make('password'),
                'terms_accepted_at' => now(),
            ]
        );

        BusinessProfile::updateOrCreate(
            ['user_id' => $mainUser->id],
            [
                'display_name' => 'Carlos Silva Serviços Elétricos',
                'store_name' => 'Auto Elétrica Silva',
                'activity_description' => 'Serviços especializados em auto elétrica, manutenção de motor de partida, alternadores, instalação de acessórios e troca de baterias.',
                'document_type' => 'cnpj',
                'document_number' => '12345678000195',
                'phone' => '+5581991234567',
                'postal_code' => '50030000',
                'street' => 'Avenida Cais do Apolo',
                'number' => '222',
                'neighborhood' => 'Bairro do Recife',
                'city' => 'Recife',
                'state' => 'PE',
            ]
        );

        app(DefaultCategoriesSeeder::class)->run($mainUser);

        if ($mainUser->clients()->count() === 0) {
            Client::factory()->count(25)->create([
                'user_id' => $mainUser->id,
            ]);
        }

        $this->seedAppointmentsForUser($mainUser, 30);
        $this->seedTransactionsAndReceiptsForUser($mainUser, 14, 8, 6);

        // 2. Garante dados para o usuário ativo existente (ex: User id=1), permitindo validação imediata no navegador
        $activeUser = User::where('id', 1)->first();
        if ($activeUser && $activeUser->id !== $mainUser->id) {
            app(DefaultCategoriesSeeder::class)->run($activeUser);

            if ($activeUser->clients()->count() === 0) {
                Client::factory()->count(15)->create([
                    'user_id' => $activeUser->id,
                ]);
            }

            $this->seedAppointmentsForUser($activeUser, 25);
            $this->seedTransactionsAndReceiptsForUser($activeUser, 12, 6, 5);
        }

        // 3. Usuários adicionais para validação de isolamento multi-inquilino (IDOR) e extrações comparativas
        $demoUsers = [
            [
                'name' => 'Mariana Souza',
                'email' => 'mariana@exemplo.com',
                'store_name' => 'Ateliê & Confeitaria Doce Sabor',
                'activity_description' => 'Bolos decorados, doces finos sob encomenda e kit festas corporativos.',
                'clients_count' => 12,
            ],
            [
                'name' => 'Rodrigo Almeida',
                'email' => 'rodrigo@exemplo.com',
                'store_name' => 'Reforma Rápida Soluções',
                'activity_description' => 'Pintura residencial, pequenos reparos hidráulicos e alvenaria expressa.',
                'clients_count' => 8,
            ],
        ];

        foreach ($demoUsers as $demo) {
            $user = User::firstOrCreate(
                ['email' => $demo['email']],
                [
                    'name' => $demo['name'],
                    'password' => Hash::make('password'),
                    'terms_accepted_at' => now(),
                ]
            );

            BusinessProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $demo['name'],
                    'store_name' => $demo['store_name'],
                    'activity_description' => $demo['activity_description'],
                ]
            );

            app(DefaultCategoriesSeeder::class)->run($user);

            if ($user->clients()->count() === 0) {
                Client::factory()->count($demo['clients_count'])->create([
                    'user_id' => $user->id,
                ]);
            }

            $this->seedAppointmentsForUser($user, 15);
            $this->seedTransactionsAndReceiptsForUser($user, 8, 5, 4);
        }
    }

    private function seedAppointmentsForUser(User $user, int $count): void
    {
        if (Appointment::withoutGlobalScopes()->where('user_id', $user->id)->exists()) {
            return;
        }

        $clients = Client::withoutGlobalScopes()->where('user_id', $user->id)->get();
        if ($clients->isEmpty()) {
            return;
        }

        Appointment::factory()
            ->count($count)
            ->state(fn () => [
                'user_id' => $user->id,
                'client_id' => $clients->random()->id,
            ])
            ->create();
    }

    private function seedTransactionsAndReceiptsForUser(
        User $user,
        int $incomeCount,
        int $expenseCount,
        int $receiptCount
    ): void {
        if (Transaction::withoutGlobalScopes()->where('user_id', $user->id)->exists()) {
            return;
        }

        $clients = Client::withoutGlobalScopes()->where('user_id', $user->id)->get();
        $incomeCategories = TransactionCategory::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('type', 'income')
            ->get();
        $expenseCategories = TransactionCategory::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->get();
        $completedAppointments = Appointment::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get()
            ->values();

        $incomes = collect();
        for ($i = 0; $i < $incomeCount; $i++) {
            $appointment = $completedAppointments->get($i);
            $clientId = $appointment?->client_id ?? ($clients->isNotEmpty() ? $clients->random()->id : null);

            $incomes->push(
                Transaction::factory()
                    ->income()
                    ->create([
                        'user_id' => $user->id,
                        'category_id' => $incomeCategories->isNotEmpty() ? $incomeCategories->random()->id : null,
                        'client_id' => $clientId,
                        'appointment_id' => $appointment?->id,
                        'amount_cents' => $appointment?->expected_amount_cents ?? fake()->numberBetween(80, 950) * 100,
                        'description' => $appointment?->service_description ?? fake()->randomElement([
                            'Pagamento de serviço técnico concluído',
                            'Venda de peças e acessórios automotivos',
                            'Manutenção preventiva e revisão completa',
                            'Atendimento avulso em domicílio',
                        ]),
                    ])
            );
        }

        for ($i = 0; $i < $expenseCount; $i++) {
            Transaction::factory()
                ->expense()
                ->create([
                    'user_id' => $user->id,
                    'category_id' => $expenseCategories->isNotEmpty() ? $expenseCategories->random()->id : null,
                ]);
        }

        $profile = $user->businessProfile()->first();
        $fullAddress = null;
        if ($profile?->street) {
            $fullAddress = "{$profile->street}, {$profile->number}";
            if ($profile->neighborhood) {
                $fullAddress .= " - {$profile->neighborhood}";
            }
            if ($profile->city && $profile->state) {
                $fullAddress .= " ({$profile->city}/{$profile->state})";
            }
        }

        $issuerSnapshot = [
            'store_name' => $profile?->store_name ?: $profile?->display_name ?: $user->name,
            'display_name' => $profile?->display_name ?: $user->name,
            'document_type' => $profile?->document_type ?: 'cpf',
            'document_number' => $profile?->document_number ?: '12345678909',
            'phone' => $profile?->phone ?: '+5581991234567',
            'full_address' => $fullAddress,
            'logo_path' => $profile?->logo_path,
            'receipt_footer' => $profile?->receipt_footer,
        ];

        $year = (int) now()->format('Y');
        $nextNumber = (Receipt::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->max('number') ?? 0) + 1;

        $receiptTransactions = $incomes->filter(fn (Transaction $t) => $t->client_id !== null)->take($receiptCount)->values();

        foreach ($receiptTransactions as $index => $transaction) {
            $client = $clients->firstWhere('id', $transaction->client_id);
            $factory = Receipt::factory();
            if ($index === $receiptTransactions->count() - 1 && $receiptCount >= 5) {
                $factory = $factory->canceled();
            }

            $factory->create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'client_id' => $transaction->client_id,
                'year' => $year,
                'number' => $nextNumber++,
                'issued_at' => $transaction->occurred_on->copy()->setTime(fake()->numberBetween(9, 17), fake()->numberBetween(0, 59)),
                'amount_cents' => $transaction->amount_cents,
                'payment_method' => $transaction->payment_method->value,
                'description' => $transaction->description,
                'payer_name' => $client?->name ?? 'Cliente Consumidor',
                'payer_document' => $client?->document_number,
                'issuer_snapshot' => $issuerSnapshot,
            ]);
        }
    }
}
