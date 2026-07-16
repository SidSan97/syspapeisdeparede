<?php

namespace App\Support;

class OrderStatus
{
    public const OPEN = 'Em aberto';

    public const APPROVED = 'Aprovado';

    public const IN_PRODUCTION = 'Em produção';

    public const SENT = 'Enviado';

    public const CANCELED = 'Cancelado';

    /**
     * Variantes case-sensitive aceitas para cada status canônico.
     *
     * @var array<string, list<string>>
     */
    public const VARIANTS = [
        self::OPEN => [
            'Em aberto',
            'em aberto',
        ],
        self::APPROVED => [
            'Aprovado',
            'aprovado',
        ],
        self::IN_PRODUCTION => [
            'Em produção',
            'em produção',
            'em producao',
        ],
        self::SENT => [
            'Enviado',
            'enviado',
        ],
        self::CANCELED => [
            'Cancelado',
            'cancelado',
        ],
    ];

    /**
     * Status canônicos do pedido.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::OPEN,
            self::APPROVED,
            self::IN_PRODUCTION,
            self::SENT,
            self::CANCELED,
        ];
    }

    /**
     * Todas as variantes aceitas (útil em Rule::in / whereIn).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::VARIANTS))));
    }

    /**
     * Variantes de um status canônico.
     *
     * @return list<string>
     */
    public static function variants(string $status): array
    {
        $canonical = self::canonicalize($status);

        if ($canonical === null) {
            return [];
        }

        return self::VARIANTS[$canonical];
    }

    /**
     * Status usados na fila de produção (com variantes).
     *
     * @return list<string>
     */
    public static function production(): array
    {
        return array_values(array_unique(array_merge(
            self::VARIANTS[self::APPROVED],
            self::VARIANTS[self::IN_PRODUCTION],
            self::VARIANTS[self::SENT],
        )));
    }

    /**
     * Normaliza qualquer variante para o valor canônico.
     */
    public static function canonicalize(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        foreach (self::VARIANTS as $canonical => $variants) {
            if (in_array($status, $variants, true)) {
                return $canonical;
            }

            foreach ($variants as $variant) {
                if (mb_strtolower($status) === mb_strtolower($variant)) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    public static function is(?string $status, string $expected): bool
    {
        return self::canonicalize($status) === self::canonicalize($expected);
    }

    public static function isValid(?string $status): bool
    {
        return self::canonicalize($status) !== null;
    }
}
