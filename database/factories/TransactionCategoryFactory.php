<?php

namespace Database\Factories;

use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    protected $model = TransactionCategory::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);
        $name = $type === 'income'
            ? fake()->randomElement(['Venda de Peças', 'Serviço Prestado', 'Consultoria', 'Comissões', 'Outras Entradas'])
            : fake()->randomElement(['Aluguel', 'Energia Elétrica', 'Insumos', 'Combustível', 'Marketing', 'Internet', 'Impostos']);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'name' => $name,
            'name_key' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'color' => fake()->randomElement(['#10B981', '#059669', '#34D399', '#EF4444', '#DC2626', '#F59E0B', '#8B5CF6', '#6B7280']),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => 'income',
            'color' => '#10B981',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => 'expense',
            'color' => '#EF4444',
        ]);
    }
}
