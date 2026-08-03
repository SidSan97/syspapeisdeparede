<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Draft = 'rascunho';
    case Open = 'em aberto';
    case Approved = 'aprovado';
    case Canceled = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Open => 'Em aberto',
            self::Approved => 'Aprovado',
            self::Canceled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'light',
            self::Open => 'info',
            self::Approved => 'success',
            self::Canceled => 'secondary',
        };
    }
}
