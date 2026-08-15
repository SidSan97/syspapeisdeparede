<?php

namespace App\Support;

class OrderBudgetStatus
{
    public const APPROVE_LAYOUT = 'Aprovar Layout';

    public const PENDING_REVIEW = 'Pendente de Revisão';

    public const LAYOUT_IN_APPROVAL = 'Layout em Aprovação';

    public const LAYOUT_APPROVED = 'Layout Aprovado';

    public const WAITING_ART = 'Aguardando Arte';

    public const ART_RECEIVED = 'Arte Recebida';

    public const RELEASED_FOR_PRODUCTION = 'Liberado para produção';

    public const APPROVED = 'Aprovado';

    /**
     * Variantes case-sensitive aceitas para cada status canônico.
     *
     * @var array<string, list<string>>
     */
    public const VARIANTS = [
        self::APPROVE_LAYOUT => [
            'Aprovar Layout',
            'aprovar layout',
        ],
        self::LAYOUT_APPROVED => [
            'Layout aprovado',
            'layout aprovado',
            'Layout Aprovado',
        ],
        self::PENDING_REVIEW => [
            'Pendente de Revisão',
            'pendente de revisão',
            'Pendente de Revisao',
            'pendente de revisao',
        ],
        self::LAYOUT_IN_APPROVAL => [
            'Layout em aprovação',
            'layout em aprovação',
            'Layout em aprovacao',
            'layout em aprovacao',
        ],
        self::WAITING_ART => [
            'Aguardando Arte',
            'aguardando arte',
        ],
        self::ART_RECEIVED => [
            'Arte Recebida',
            'arte recebida',
        ],
        self::RELEASED_FOR_PRODUCTION => [
            'Liberado para produção',
            'liberado para produção',
            'Liberado para producao',
            'liberado para producao',
        ],
        self::APPROVED => [
            'Aprovado',
            'aprovado',
        ],
    ];

    /**
     * Status canônicos do card (order_budget).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::APPROVE_LAYOUT,
            self::PENDING_REVIEW,
            self::LAYOUT_IN_APPROVAL,
            self::LAYOUT_APPROVED,
            self::WAITING_ART,
            self::ART_RECEIVED,
            self::RELEASED_FOR_PRODUCTION,
            self::APPROVED,
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
     * Status da fila de aprovação de layout (com variantes).
     *
     * @return list<string>
     */
    public static function layoutApprovalQueue(): array
    {
        return array_values(array_unique(array_merge(
            self::VARIANTS[self::APPROVE_LAYOUT],
            self::VARIANTS[self::PENDING_REVIEW],
            self::VARIANTS[self::LAYOUT_IN_APPROVAL],
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
