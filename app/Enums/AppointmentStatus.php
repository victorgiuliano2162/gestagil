<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Em Andamento',
            self::Completed => 'Concluído',
            self::Canceled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'amber',
            self::Completed => 'emerald',
            self::Canceled => 'rose',
        };
    }

    /**
     * Valida se a transição de status é permitida (RN-04)
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::InProgress => in_array($target, [self::Completed, self::Canceled]),
            self::Completed => false, // Estado final
            self::Canceled => false,  // Estado final
        };
    }
}
