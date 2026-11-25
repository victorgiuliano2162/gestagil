<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    private const SERVICE_DESCRIPTIONS = [
        'Manutenção preventiva completa e revisão geral',
        'Diagnóstico técnico com scanner e orçamento detalhado',
        'Instalação de equipamento e configuração inicial',
        'Reparo de circuito elétrico e substituição de componentes',
        'Limpeza técnica especializada e troca de peças desgastadas',
        'Atendimento emergencial em domicílio para falha operacional',
        'Troca de bateria, testes de carga e calibração',
        'Revisão de sistema com emissão de laudo técnico',
        'Atualização de firmware, ajustes finos e teste de bancada',
        'Substituição de módulo danificado e garantia de funcionamento',
    ];

    private const CANCEL_REASONS = [
        'Cliente solicitou reagendamento para a próxima semana.',
        'Imprevisto pessoal do cliente no horário agendado.',
        'Peça necessária indisponível em estoque no momento.',
    ];

    public function definition(): array
    {
        $status = fake()->randomElement([
            AppointmentStatus::InProgress,
            AppointmentStatus::InProgress,
            AppointmentStatus::Completed,
            AppointmentStatus::Completed,
            AppointmentStatus::Canceled,
        ]);

        $serviceAt = match ($status) {
            AppointmentStatus::InProgress => fake()->dateTimeBetween('-5 days', '+15 days'),
            default => fake()->dateTimeBetween('-2 months', 'now'),
        };

        $completedAt = $status === AppointmentStatus::Completed
            ? (clone $serviceAt)->modify('+'.fake()->numberBetween(1, 4).' hours')
            : null;

        $canceledAt = $status === AppointmentStatus::Canceled
            ? (clone $serviceAt)->modify('-'.fake()->numberBetween(1, 24).' hours')
            : null;

        $cancelReason = $status === AppointmentStatus::Canceled
            ? fake()->randomElement(self::CANCEL_REASONS)
            : null;

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'service_description' => fake()->randomElement(self::SERVICE_DESCRIPTIONS),
            'service_at' => $serviceAt,
            'status' => $status,
            'technical_notes' => fake()->optional(0.6)->sentence(),
            'expected_amount_cents' => fake()->numberBetween(50, 850) * 100,
            'completed_at' => $completedAt,
            'canceled_at' => $canceledAt,
            'cancel_reason' => $cancelReason,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::InProgress,
            'completed_at' => null,
            'canceled_at' => null,
            'cancel_reason' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Completed,
            'completed_at' => now(),
            'canceled_at' => null,
            'cancel_reason' => null,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Canceled,
            'completed_at' => null,
            'canceled_at' => now(),
            'cancel_reason' => fake()->randomElement(self::CANCEL_REASONS),
        ]);
    }
}
