<?php

namespace App\Support\Budget;

use App\Models\CollectionModel;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class BudgetCalculator
{
    protected static ?array $tinyErpAllData = null;

    public const STRIP_WIDTH = 0.6;

    private const EXTRA = 0.07;

    private const MAX_H = 12.0;

    public const STRIP_HEIGHT_OPTIONS = [
        1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6,
        3.7, 3.8, 3.9, 4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0,
        5.1, 5.2, 5.3, 5.4, 5.5, 6.0, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8,
        6.9, 7.0, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 8.0,
    ];

    private static ?array $alturasFaixa = null;

    protected static array $modelCache = [];

    /**
     * Obtém os dados do Tiny ERP do cache
     */
    protected static function getTinyErpAllData(): array
    {
        if (self::$tinyErpAllData === null) {
            self::$tinyErpAllData = Cache::get('tiny_erp_all_data', []);
        }

        return self::$tinyErpAllData;
    }

    /**
     * Obtém o preço à vista do cache (fallback: settings persistidos).
     */
    protected static function getPriceVista(): float
    {
        $data = self::getTinyErpAllData();
        $price = (float) ($data['precoPromocionalVista'] ?? 0);

        if ($price <= 0) {
            $price = (float) Setting::get('tiny_erp_price_payment', 0);
        }

        return $price;
    }

    /**
     * Obtém o preço a prazo do cache (fallback: settings persistidos).
     */
    protected static function getPricePrazo(): float
    {
        $data = self::getTinyErpAllData();
        $price = (float) ($data['precoPromocionalPrazo'] ?? 0);

        if ($price <= 0) {
            $price = (float) Setting::get('tiny_erp_price_installment', 0);
        }

        return $price;
    }

    protected static function getContinuations(array $wall): array
    {
        $enabled = $wall['continueSameArt'] ?? ($wall['continue_same_art'] ?? false);
        if (empty($enabled)) {
            return [];
        }

        $continuations = $wall['continuations'] ?? [];

        if (! is_array($continuations)) {
            return [];
        }

        return array_values(array_filter($continuations, function ($continuation) {
            return is_array($continuation);
        }));
    }

    /**
     * Constrói a mesma lista de alturas do HTML (base + 5.60..12.00 passo 0.10).
     * Mantém valores únicos com 2 casas decimais.
     *
     * @return float[]
     */
    private static function getAlturasFaixa(): array
    {
        if (self::$alturasFaixa !== null) {
            return self::$alturasFaixa;
        }

        $out = [];
        foreach (self::STRIP_HEIGHT_OPTIONS as $v) {
            $out[(string) ((float) $v)] = (float) $v;
        }

        for ($x = 5.6; $x <= self::MAX_H + 1e-9; $x += 0.1) {
            // idem HTML: Math.round(x*100)/100
            $v = round($x, 2);
            $out[(string) $v] = $v;
        }

        $alturas = array_values($out);
        sort($alturas);

        return self::$alturasFaixa = $alturas;
    }

    private static function getAlturaFaixa(float $alturaParede): ?float
    {
        if ($alturaParede <= 0) {
            return null;
        }

        $target = $alturaParede + self::EXTRA;
        if ($target > self::MAX_H + 1e-9) {
            return null;
        }

        $alturas = self::getAlturasFaixa();
        foreach ($alturas as $h) {
            if ($h + 1e-9 >= $target) {
                return $h;
            }
        }

        return null;
    }

    private static function collapseWallForSequence(array $wall): ?array
    {
        $baseL = (float) ($wall['width'] ?? 0);
        $baseA = (float) ($wall['height'] ?? 0);

        $continuations = self::getContinuations($wall);
        $continuationWidth = 0.0;
        $maxH = $baseA > 0 ? $baseA : 0.0;

        foreach ($continuations as $cont) {
            $L = (float) ($cont['width'] ?? 0);
            $A = (float) ($cont['height'] ?? 0);

            if ($L > 0) {
                $continuationWidth += $L;
            }
            if ($A > 0 && $A > $maxH) {
                $maxH = $A;
            }
        }

        $L = $baseL + $continuationWidth;
        $A = $maxH;

        if ($L <= 0 || $A <= 0) {
            return null;
        }

        return ['L' => $L, 'A' => $A];
    }

    private static function mergeConsecutiveGroups(array $groups): array
    {
        $out = [];

        foreach ($groups as $g) {
            if (! $g || ($g['q'] ?? 0) <= 0) {
                continue;
            }

            if (! empty($out) && abs($out[count($out) - 1]['h'] - $g['h']) < 1e-9) {
                $out[count($out) - 1]['q'] += $g['q'];
                $out[count($out) - 1]['indices'] = array_merge(
                    $out[count($out) - 1]['indices'],
                    $g['indices'] ?? []
                );
            } else {
                $out[] = [
                    'h' => (float) $g['h'],
                    'q' => (int) $g['q'],
                    'indices' => $g['indices'] ?? [],
                ];
            }
        }

        return $out;
    }

    private static function mustBeEvenGroups(array $groups, int $k): bool
    {
        $last = count($groups) - 1;
        $middleRule = $k !== 0 && $k !== $last;
        $heightRule = $groups[$k]['h'] > 6.0 + 1e-9;

        return $middleRule || $heightRule;
    }

    private static function rebalanceParityGroups(array $groups): array
    {
        $changed = true;
        $safety = 0;

        $findNextGE = function (int $k) use (&$groups): int {
            $hk = $groups[$k]['h'];
            for ($j = $k + 1; $j < count($groups); $j++) {
                if ($groups[$j]['h'] + 1e-9 >= $hk) {
                    return $j;
                }
            }

            return -1;
        };

        while ($changed && $safety < 800) {
            $safety++;
            $changed = false;

            $groups = self::mergeConsecutiveGroups($groups);

            for ($k = 0; $k < count($groups); $k++) {
                if (($groups[$k]['q'] ?? 0) <= 0) {
                    continue;
                }

                if (self::mustBeEvenGroups($groups, $k) && ($groups[$k]['q'] % 2 !== 0)) {
                    $hk = $groups[$k]['h'];
                    $last = count($groups) - 1;

                    $j = $findNextGE($k);

                    if ($j !== -1) {
                        $groups[$k]['q'] -= 1;
                        $groups[$j]['q'] += 1;
                        $groups = self::mergeConsecutiveGroups($groups);
                        $changed = true;
                        break;
                    }

                    if ($k < $last && ($groups[$k + 1]['q'] ?? 0) > 0) {
                        $groups[$k + 1]['q'] -= 1;
                        $groups[$k]['q'] += 1;
                        $groups = self::mergeConsecutiveGroups($groups);
                        $changed = true;
                        break;
                    }

                    $groups[$k]['q'] += 1;
                    $groups = self::mergeConsecutiveGroups($groups);
                    $changed = true;
                    break;
                }
            }
        }

        return ['groups' => $groups, 'safetyExceeded' => $safety >= 800];
    }

    /**
     * Calcula os strips de uma sala na ordem das paredes (carry + subida de altura + paridade).
     * Retorna métricas por parede na MESMA ordem do array de entrada.
     *
     * @return array{perWall: array<int, array{strip_count:int, strip_height:?float, total_area:float}>, totalFaixas:int, totalMetros:float, safetyExceeded:bool}
     */
    public static function calculateWallsSequence(array $walls): array
    {
        if (empty($walls)) {
            return [
                'perWall' => [],
                'totalFaixas' => 0,
                'totalMetros' => 0,
                'safetyExceeded' => false,
            ];
        }

        $collapsed = [];
        $stripHeights = [];
        $valid = true;

        foreach ($walls as $i => $wall) {
            $seg = self::collapseWallForSequence($wall);
            if ($seg === null) {
                $valid = false;
                break;
            }

            $h = self::getAlturaFaixa((float) $seg['A']);
            if ($h === null) {
                $valid = false;
                break;
            }

            $collapsed[$i] = $seg;
            $stripHeights[$i] = $h;
        }

        if (! $valid) {
            $perWall = [];
            foreach ($walls as $_) {
                $perWall[] = ['strip_count' => 0, 'strip_height' => null, 'total_area' => 0.0];
            }

            return [
                'perWall' => $perWall,
                'totalFaixas' => 0,
                'totalMetros' => 0.0,
                'safetyExceeded' => false,
            ];
        }

        // Passo A: paredes -> perWallBase (q,h) com carry e subida de altura
        $carry = 0.0;
        $perWallBase = [];

        $n = count($walls);
        for ($i = 0; $i < $n; $i++) {
            $Li = (float) $collapsed[$i]['L'];
            $Hi = (float) $stripHeights[$i];
            $nextH = $i < $n - 1 ? (float) $stripHeights[$i + 1] : null;

            $LiEfetiva = $Li - $carry;

            if ($LiEfetiva <= 1e-12) {
                $carry = -$LiEfetiva;
                $perWallBase[] = ['q' => 0, 'h' => $Hi];

                continue;
            }

            $qBruto = (int) ceil($LiEfetiva / self::STRIP_WIDTH);
            $cobertura = $qBruto * self::STRIP_WIDTH;
            $novoCarry = $cobertura - $LiEfetiva;

            if ($i < $n - 1 && $nextH !== null && $nextH > $Hi + 1e-9 && $qBruto > 0) {
                $q = $qBruto - 1;
                $cobAgora = $q * self::STRIP_WIDTH;
                $faltou = $LiEfetiva - $cobAgora; // > 0
                $carry = -$faltou;
                $perWallBase[] = ['q' => $q, 'h' => $Hi];
            } else {
                $carry = $novoCarry;
                $perWallBase[] = ['q' => $qBruto, 'h' => $Hi];
            }
        }

        // mergeConsecutive -> grupos com índices
        $groups0 = [];
        for ($i = 0; $i < count($perWallBase); $i++) {
            $q = (int) ($perWallBase[$i]['q'] ?? 0);
            $h = (float) ($perWallBase[$i]['h'] ?? 0);
            if ($q <= 0) {
                continue;
            }

            if (! empty($groups0) && abs($groups0[count($groups0) - 1]['h'] - $h) < 1e-9) {
                $groups0[count($groups0) - 1]['q'] += $q;
                $groups0[count($groups0) - 1]['indices'][] = $i;
            } else {
                $groups0[] = ['h' => $h, 'q' => $q, 'indices' => [$i]];
            }
        }

        $bres = self::rebalanceParityGroups($groups0);
        $groupsFinal = self::mergeConsecutiveGroups($bres['groups']);

        // totals
        $totalFaixas = 0;
        $totalMetros = 0.0;
        foreach ($groupsFinal as $g) {
            $totalFaixas += (int) $g['q'];
            $totalMetros += (int) $g['q'] * (float) $g['h'];
        }

        // Distribui q final para cada parede por proporção das q-base dentro do grupo
        $wallStrips = array_fill(0, $n, 0);
        $wallStripHeights = array_fill(0, $n, null);

        foreach ($groupsFinal as $g) {
            $indices = $g['indices'] ?? [];
            if (empty($indices)) {
                continue;
            }

            $qBaseSum = 0;
            foreach ($indices as $idx) {
                $qBaseSum += (int) ($perWallBase[$idx]['q'] ?? 0);
            }
            if ($qBaseSum <= 0) {
                continue;
            }

            $hk = (float) $g['h'];
            foreach ($indices as $idx) {
                $wallStripHeights[$idx] = $hk;
            }

            $alloc = [];
            $allocatedSum = 0;
            foreach ($indices as $idx) {
                $q0 = (int) ($perWallBase[$idx]['q'] ?? 0);
                $exact = ($q0 / $qBaseSum) * (int) $g['q'];

                $base = (int) floor($exact + 1e-9);
                $frac = $exact - $base;
                $alloc[] = ['idx' => $idx, 'base' => $base, 'frac' => $frac];
                $allocatedSum += $base;
            }

            $remainder = (int) $g['q'] - $allocatedSum;
            if ($remainder > 0) {
                usort($alloc, fn ($a, $b) => ($b['frac'] <=> $a['frac']));
                for ($r = 0; $r < $remainder; $r++) {
                    $alloc[$r]['base'] += 1;
                }
            } elseif ($remainder < 0) {
                $remainderAbs = abs($remainder);
                usort($alloc, fn ($a, $b) => ($a['frac'] <=> $b['frac']));
                for ($r = 0; $r < $remainderAbs; $r++) {
                    if ($alloc[$r]['base'] > 0) {
                        $alloc[$r]['base'] -= 1;
                    }
                }
            }

            foreach ($alloc as $e) {
                $wallStrips[$e['idx']] = (int) $e['base'];
            }
        }

        $perWall = [];
        for ($i = 0; $i < $n; $i++) {
            $stripHeight = $wallStripHeights[$i];
            $stripCount = (int) $wallStrips[$i];
            $meters = ($stripHeight === null) ? 0.0 : ($stripCount * (float) $stripHeight);

            $perWall[] = [
                'strip_count' => $stripCount,
                'strip_height' => $stripHeight,
                'total_area' => round($meters, 2),
            ];
        }

        return [
            'perWall' => $perWall,
            'totalFaixas' => $totalFaixas,
            'totalMetros' => round($totalMetros, 2),
            'safetyExceeded' => (bool) ($bres['safetyExceeded'] ?? false),
        ];
    }

    /**
     * Calcula a área da parede como: quantidade de faixas × tamanho da faixa
     * (não é m², é metros)
     */
    public static function calculateWallArea(array $wall): float
    {
        $strips = self::calculateStripCount($wall);
        $stripHeight = self::calculateStripHeight($wall);

        if ($strips > 0 && $stripHeight !== null) {
            return round($strips * $stripHeight, 2);
        }

        return 0.0;
    }

    public static function calculateStripHeight(array $wall): ?float
    {
        // Combina base + continuações e aplica a mesma lógica do HTML:
        // - target = altura + EXTRA
        // - procura na tabela ALTURAS estendida (STRIP_HEIGHT_OPTIONS base + 5.6..MAX_H)
        $heights = [(float) ($wall['height'] ?? 0)];
        foreach (self::getContinuations($wall) as $continuation) {
            $heights[] = (float) ($continuation['height'] ?? 0);
        }

        $height = max($heights);
        if ($height <= 0) {
            return null;
        }

        return self::getAlturaFaixa((float) $height);
    }

    public static function calculateStripCount(array $wall): int
    {
        $width = (float) ($wall['width'] ?? 0);
        $continuationWidth = 0;

        foreach (self::getContinuations($wall) as $continuation) {
            $continuationWidth += (float) ($continuation['width'] ?? 0);
        }

        $width += $continuationWidth;

        if ($width <= 0) {
            return 0;
        }

        $numberOfStrips = (int) ceil($width / self::STRIP_WIDTH);
        $stripHeight = self::calculateStripHeight($wall);

        // Regra de paridade do HTML:
        // - grupos com h > 6.0 precisam ser pares.
        if ($stripHeight !== null && $stripHeight > 6.0 + 1e-9 && $numberOfStrips % 2 !== 0) {
            $numberOfStrips += 1;
        }

        return $numberOfStrips;
    }

    public static function calculateTotalArea(array $rooms): float
    {
        $total = 0.0;

        foreach ($rooms as $room) {
            $walls = $room['walls'] ?? [];
            $seq = self::calculateWallsSequence($walls);

            foreach ($seq['perWall'] as $wallMetrics) {
                $total += (float) ($wallMetrics['total_area'] ?? 0);
            }
        }

        return round($total, 2);
    }

    protected static function getModel(int|string|null $modelId): ?CollectionModel
    {
        if ($modelId === null) {
            return null;
        }

        if (! array_key_exists($modelId, self::$modelCache)) {
            self::$modelCache[$modelId] = CollectionModel::find($modelId);
        }

        return self::$modelCache[$modelId];
    }

    public static function calculateModelCost(array $rooms): float
    {
        $total = 0;

        foreach ($rooms as $room) {
            foreach ($room['walls'] ?? [] as $wall) {
                $model = self::getModel($wall['model'] ?? null);

                if ($model !== null) {
                    $total += (float) $model->value;
                }
            }
        }

        return round($total, 2);
    }

    /**
     * Calcula o total à vista: metros × precoVista + modelos + frete
     */
    public static function calculateTotalAmountVista(
        float $totalArea,
        array $rooms,
        ?array $selectedCarrier
    ): float {
        self::$modelCache = [];

        $subtotal = $totalArea * self::getPriceVista();
        $subtotal += self::calculateModelCost($rooms);

        if ($selectedCarrier !== null) {
            $subtotal += (float) ($selectedCarrier['price'] ?? 0);
        }

        return round($subtotal, 2);
    }

    /**
     * Calcula o total a prazo: metros × precoPrazo + modelos + frete
     */
    public static function calculateTotalAmountPrazo(
        float $totalArea,
        array $rooms,
        ?array $selectedCarrier
    ): float {
        self::$modelCache = [];

        $subtotal = $totalArea * self::getPricePrazo();
        $subtotal += self::calculateModelCost($rooms);

        if ($selectedCarrier !== null) {
            $subtotal += (float) ($selectedCarrier['price'] ?? 0);
        }

        return round($subtotal, 2);
    }

    /**
     * Calcula o total baseado na forma de pagamento (mantido para compatibilidade)
     */
    public static function calculateTotalAmount(
        float $totalArea,
        array $rooms,
        ?array $selectedCarrier,
        ?string $paymentMethod
    ): float {
        if ($paymentMethod === 'pix') {
            return self::calculateTotalAmountVista($totalArea, $rooms, $selectedCarrier);
        } elseif ($paymentMethod === 'credit_card') {
            return self::calculateTotalAmountPrazo($totalArea, $rooms, $selectedCarrier);
        }

        // Default: retorna o valor à vista
        return self::calculateTotalAmountVista($totalArea, $rooms, $selectedCarrier);
    }

    public static function calculateDeliveryTime(array $rooms, ?array $selectedCarrier): int
    {
        self::$modelCache = [];

        $maxDevelopmentDays = 0;

        foreach ($rooms as $room) {
            foreach ($room['walls'] ?? [] as $wall) {
                $model = self::getModel($wall['model'] ?? null);

                if ($model === null) {
                    continue;
                }

                $developmentDays = (int) ($model->deadline ?? 0);

                if ($developmentDays > $maxDevelopmentDays) {
                    $maxDevelopmentDays = $developmentDays;
                }
            }
        }

        $productionTime = 5;
        $freightTime = (int) ($selectedCarrier['deliveryTime'] ?? 0);

        return $productionTime + $maxDevelopmentDays + $freightTime;
    }

    /**
     * Segmentos de uma parede: parede principal + continuações (ordem preservada).
     *
     * @return array<int, array{L: float, A: float}>
     */
    private static function getWallSegments(array $wall): array
    {
        $segments = [];

        $baseL = (float) ($wall['width'] ?? 0);
        $baseA = (float) ($wall['height'] ?? 0);
        if ($baseL > 0 && $baseA > 0) {
            $segments[] = ['L' => $baseL, 'A' => $baseA];
        }

        foreach (self::getContinuations($wall) as $continuation) {
            $L = (float) ($continuation['width'] ?? 0);
            $A = (float) ($continuation['height'] ?? 0);
            if ($L > 0 && $A > 0) {
                $segments[] = ['L' => $L, 'A' => $A];
            }
        }

        return $segments;
    }

    /**
     * Algoritmo da Calculadora do Revendedor aplicado a parede + continuações em sequência.
     *
     * @return array{
     *     groups: array<int, array{q: int, h: float}>,
     *     totalFaixas: int,
     *     totalMetros: float,
     *     safetyExceeded: bool
     * }
     */
    public static function calculateWallWithContinuations(array $wall): array
    {
        $segments = self::getWallSegments($wall);

        if (empty($segments)) {
            return [
                'groups' => [],
                'totalFaixas' => 0,
                'totalMetros' => 0.0,
                'safetyExceeded' => false,
            ];
        }

        $H = [];
        foreach ($segments as $segment) {
            $h = self::getAlturaFaixa((float) $segment['A']);
            if ($h === null) {
                return [
                    'groups' => [],
                    'totalFaixas' => 0,
                    'totalMetros' => 0.0,
                    'safetyExceeded' => false,
                ];
            }
            $H[] = $h;
        }

        $carry = 0.0;
        $perPartBase = [];
        $n = count($segments);

        for ($i = 0; $i < $n; $i++) {
            $Li = (float) $segments[$i]['L'];
            $Hi = (float) $H[$i];
            $nextH = $i < $n - 1 ? (float) $H[$i + 1] : null;

            $LiEfetiva = $Li - $carry;

            if ($LiEfetiva <= 1e-12) {
                $carry = -$LiEfetiva;
                $perPartBase[] = ['q' => 0, 'h' => $Hi];

                continue;
            }

            $qBruto = (int) ceil($LiEfetiva / self::STRIP_WIDTH);
            $cobertura = $qBruto * self::STRIP_WIDTH;
            $novoCarry = $cobertura - $LiEfetiva;

            if ($i < $n - 1 && $nextH !== null && $nextH > $Hi + 1e-9 && $qBruto > 0) {
                $q = $qBruto - 1;
                $cobAgora = $q * self::STRIP_WIDTH;
                $faltou = $LiEfetiva - $cobAgora;
                $carry = -$faltou;
                $perPartBase[] = ['q' => $q, 'h' => $Hi];
            } else {
                $carry = $novoCarry;
                $perPartBase[] = ['q' => $qBruto, 'h' => $Hi];
            }
        }

        $groups0 = [];
        for ($i = 0; $i < count($perPartBase); $i++) {
            $q = (int) ($perPartBase[$i]['q'] ?? 0);
            $h = (float) ($perPartBase[$i]['h'] ?? 0);
            if ($q <= 0) {
                continue;
            }

            if (! empty($groups0) && abs($groups0[count($groups0) - 1]['h'] - $h) < 1e-9) {
                $groups0[count($groups0) - 1]['q'] += $q;
            } else {
                $groups0[] = ['h' => $h, 'q' => $q, 'indices' => [$i]];
            }
        }

        $bres = self::rebalanceParityGroups($groups0);
        $groupsFinal = self::mergeConsecutiveGroups($bres['groups']);

        $totalFaixas = 0;
        $totalMetros = 0.0;
        $groups = [];

        foreach ($groupsFinal as $group) {
            $q = (int) ($group['q'] ?? 0);
            $h = (float) ($group['h'] ?? 0);
            if ($q <= 0 || $h <= 0) {
                continue;
            }

            $totalFaixas += $q;
            $totalMetros += $q * $h;
            $groups[] = ['q' => $q, 'h' => $h];
        }

        return [
            'groups' => $groups,
            'totalFaixas' => $totalFaixas,
            'totalMetros' => round($totalMetros, 2),
            'safetyExceeded' => (bool) ($bres['safetyExceeded'] ?? false),
        ];
    }

    /**
     * @param  array<int, array{q: int, h: float}>  $groups
     */
    public static function formatStripGroups(array $groups): string
    {
        $parts = [];

        foreach ($groups as $group) {
            $q = (int) ($group['q'] ?? 0);
            $h = (float) ($group['h'] ?? 0);
            if ($q <= 0 || $h <= 0) {
                continue;
            }

            $parts[] = sprintf('%dF de %sm', $q, number_format($h, 2, ',', '.'));
        }

        return implode(' + ', $parts);
    }

    /**
     * Converte parede Eloquent/array para formato de cálculo.
     */
    public static function normalizeWallForCalculation(object|array $wall): array
    {
        if (is_array($wall)) {
            return $wall;
        }

        return [
            'width' => $wall->width,
            'height' => $wall->height,
            'continue_same_art' => (bool) ($wall->continue_same_art ?? false),
            'continuations' => is_array($wall->continuations ?? null) ? $wall->continuations : [],
        ];
    }

    /**
     * Normaliza paredes de um ambiente para o mesmo formato usado em calculateTotalArea / calculateWallsSequence.
     *
     * @return array<int, array{width: mixed, height: mixed, continueSameArt: bool, continuations: array, model: mixed}>
     */
    public static function normalizeRoomWallsForCalculation(object|array $room): array
    {
        $walls = is_array($room) ? ($room['walls'] ?? []) : ($room->walls ?? []);
        $normalized = [];

        foreach ($walls as $wall) {
            if (is_array($wall)) {
                $normalized[] = [
                    'width' => $wall['width'] ?? null,
                    'height' => $wall['height'] ?? null,
                    'continueSameArt' => (bool) ($wall['continueSameArt'] ?? $wall['continue_same_art'] ?? false),
                    'continuations' => is_array($wall['continuations'] ?? null) ? $wall['continuations'] : [],
                    'model' => $wall['model'] ?? ($wall['collection_model_id'] ?? null),
                ];

                continue;
            }

            $normalized[] = [
                'width' => $wall->width,
                'height' => $wall->height,
                'continueSameArt' => (bool) ($wall->continue_same_art ?? false),
                'continuations' => is_array($wall->continuations ?? null) ? $wall->continuations : [],
                'model' => $wall->collection_model_id,
            ];
        }

        return $normalized;
    }

    public static function calculateRoomMeters(object|array $room): float
    {
        $sequence = self::calculateWallsSequence(self::normalizeRoomWallsForCalculation($room));

        return round((float) ($sequence['totalMetros'] ?? 0), 2);
    }

    public static function calculateRoomModelCost(object|array $room): float
    {
        $walls = is_array($room) ? ($room['walls'] ?? []) : ($room->walls ?? []);
        $total = 0.0;

        foreach ($walls as $wall) {
            if (is_array($wall)) {
                $model = self::getModel($wall['model'] ?? ($wall['collection_model_id'] ?? null));
            } else {
                $model = $wall->relationLoaded('collectionModel')
                    ? $wall->collectionModel
                    : self::getModel($wall->collection_model_id);
            }

            if ($model !== null) {
                $total += (float) $model->value;
            }
        }

        return round($total, 2);
    }

    public static function calculateRoomPriceVista(object|array $room): float
    {
        $meters = self::calculateRoomMeters($room);
        $modelCost = self::calculateRoomModelCost($room);

        return round(($meters * self::getPriceVista()) + $modelCost, 2);
    }

    public static function calculateRoomPricePrazo(object|array $room): float
    {
        $meters = self::calculateRoomMeters($room);
        $modelCost = self::calculateRoomModelCost($room);

        return round(($meters * self::getPricePrazo()) + $modelCost, 2);
    }
}
