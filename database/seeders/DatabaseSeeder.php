<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\Client;
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

        // 2. Garante dados para o usuário ativo existente (ex: User id=1), permitindo validação imediata no navegador
        $activeUser = User::where('id', 1)->first();
        if ($activeUser && $activeUser->id !== $mainUser->id && $activeUser->clients()->count() === 0) {
            Client::factory()->count(15)->create([
                'user_id' => $activeUser->id,
            ]);
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
        }
    }
}
