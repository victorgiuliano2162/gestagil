<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultCategoriesSeeder extends Seeder
{
    public const DEFAULT_CATEGORIES = [
        'income' => [
            ['name' => 'Vendas de Produtos', 'color' => '#10B981'],
            ['name' => 'Prestação de Serviços', 'color' => '#059669'],
            ['name' => 'Outras Receitas', 'color' => '#34D399'],
        ],
        'expense' => [
            ['name' => 'Fornecedores / Mercadorias', 'color' => '#EF4444'],
            ['name' => 'Aluguel / Condomínio', 'color' => '#DC2626'],
            ['name' => 'Energia / Água / Internet', 'color' => '#F59E0B'],
            ['name' => 'Marketing e Vendas', 'color' => '#8B5CF6'],
            ['name' => 'Impostos e Taxas', 'color' => '#6B7280'],
            ['name' => 'Outras Despesas', 'color' => '#9CA3AF'],
        ],
    ];

    public function run(?User $user = null): void
    {
        $users = $user ? collect([$user]) : User::all();

        foreach ($users as $u) {
            foreach (self::DEFAULT_CATEGORIES as $type => $categories) {
                foreach ($categories as $cat) {
                    $u->categories()->firstOrCreate(
                        [
                            'type' => $type,
                            'name_key' => Str::slug($cat['name']),
                        ],
                        [
                            'name' => $cat['name'],
                            'color' => $cat['color'],
                        ]
                    );
                }
            }
        }
    }
}
