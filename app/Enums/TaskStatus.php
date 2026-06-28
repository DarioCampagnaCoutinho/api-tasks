<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Completed  = 'completed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pendente',
            self::InProgress => 'Em andamento',
            self::Completed  => 'Concluída',
        };
    }
}
