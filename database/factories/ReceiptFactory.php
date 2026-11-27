<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        $year = (int) now()->format('Y');

        return [
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory()->income(),
            'client_id' => null,
            'code' => Str::uuid()->toString(),
            'year' => $year,
            'number' => fake()->unique()->numberBetween(1, 99999),
            'status' => 'issued',
            'issued_at' => fake()->dateTimeBetween(now()->startOfMonth(), 'now'),
            'amount_cents' => fake()->numberBetween(80, 950) * 100,
            'payment_method' => fake()->randomElement(PaymentMethod::cases())->value,
            'description' => 'Referente à prestação de serviços técnicos e manutenção.',
            'payer_name' => fake()->name(),
            'payer_document' => null,
            'issuer_snapshot' => [
                'store_name' => 'Minha Loja',
                'display_name' => 'Prestador de Serviços',
                'document_type' => 'cpf',
                'document_number' => '12345678909',
                'phone' => '+5581991234567',
                'full_address' => null,
                'logo_path' => null,
                'receipt_footer' => null,
            ],
            'canceled_at' => null,
            'cancel_reason' => null,
        ];
    }

    public function canceled(): static
    {
        return $this->state(fn () => [
            'status' => 'canceled',
            'canceled_at' => now(),
            'cancel_reason' => 'Recibo emitido com valor incorreto; substituído por novo lançamento.',
        ]);
    }
}
