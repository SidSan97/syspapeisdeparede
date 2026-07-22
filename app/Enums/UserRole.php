<?php

namespace App\Enums;

enum UserRole: string
{
    case Reseller = 'reseller';
    case Designer = 'designer';
    case Production = 'production';
    case Commercial = 'commercial';
    case Expedition = 'expedition';
    case Representative = 'representatives'; // FIXME: typo plural.
    case Architect = 'architects'; // FIXME: typo plural.
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Reseller => 'Revendedor',
            self::Designer => 'Designer',
            self::Production => 'Produção',
            self::Commercial => 'Comercial',
            self::Expedition => 'Expedição',
            self::Representative => 'Representante',
            self::Architect => 'Arquiteto',
            self::Admin => 'Administrador',
        };
    }

    public static function internal(): array
    {
        return array_filter(
            self::cases(),
            fn (self $role) => $role !== self::Reseller
        );
    }
}
