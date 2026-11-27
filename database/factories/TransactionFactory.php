<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    private const INCOME_DESCRIPTIONS = [
        'Pagamento de serviço técnico concluído',
        'Venda de peças e acessórios automotivos',
        'Manutenção preventiva e revisão completa',
        'Instalação de equipamento e configuração',
        'Atendimento avulso em domicílio',
        'Troca de bateria e teste de carga',
        'Diagnóstico técnico com emissão de laudo',
        'Sinal de orçamento aprovado pelo cliente',
    ];

    private const EXPENSE_DESCRIPTIONS = [
        'Compra de peças e insumos no fornecedor',
        'Pagamento de energia elétrica e internet',
        'Aluguel do ponto comercial',
        'Reposição de estoque de componentes',
        'Taxa de maquininha e tarifa bancária',
        'Combustível e deslocamento para atendimentos',
        'Material de limpeza e manutenção da loja',
        'Impulsionamento e divulgação local',
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'income', 'expense']);
        $descriptions = $type === 'income'
            ? self::INCOME_DESCRIPTIONS
            : self::EXPENSE_DESCRIPTIONS;

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'amount_cents' => fake()->numberBetween(45, 950) * 100,
            'occurred_on' => fake()->dateTimeBetween(now()->startOfMonth(), 'now')->format('Y-m-d'),
            'description' => fake()->randomElement($descriptions),
            'category_id' => TransactionCategory::factory(),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'appointment_id' => null,
            'client_id' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => 'income',
            'description' => fake()->randomElement(self::INCOME_DESCRIPTIONS),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => 'expense',
            'description' => fake()->randomElement(self::EXPENSE_DESCRIPTIONS),
        ]);
    }
}
