<?php

namespace App\Support;

class UserType
{
    public const ADMIN = 1;
    public const RESELLER = 2;
    public const DESIGNER = 3;
    public const PRODUCTION = 4;
    public const COMMERCIAL = 5;
    public const EXPEDITION = 6;
    public const REPRESENTATIVES = 7;
    public const ARCHITECTS = 8;

    public const ROLE_MAP = [
        self::ADMIN => 'admin',
        self::RESELLER => 'reseller',
        self::DESIGNER => 'designer',
        self::PRODUCTION => 'production',
        self::COMMERCIAL => 'commercial',
        self::EXPEDITION => 'expedition',
        self::REPRESENTATIVES => 'representatives',
        self::ARCHITECTS => 'architects',
    ];

    public static function getRoleName(int $userTypeId): ?string
    {
        return self::ROLE_MAP[$userTypeId] ?? null;
    }

    public static function getUserTypeId(string $roleName): ?int
    {
        $flipped = array_flip(self::ROLE_MAP);
        return $flipped[$roleName] ?? null;
    }

    public static function isValid(int $userTypeId): bool
    {
        return isset(self::ROLE_MAP[$userTypeId]);
    }
}

