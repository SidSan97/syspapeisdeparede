<?php

namespace App\Support\Budget;

use App\Models\CollectionModel;

class BudgetCalculator
{
    public const PRICE_PER_SQUARE_METER = 50;
    public const STRIP_WIDTH = 0.6;

    public const STRIP_HEIGHT_OPTIONS = [
        1.0, 1.2, 1.5, 1.7, 2.0, 2.2, 2.5, 2.7, 3.0, 3.2, 3.3, 3.4, 3.5, 3.6,
        3.7, 3.8, 3.9, 4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.0,
        5.1, 5.2, 5.3, 5.4, 5.5, 6.0, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8,
        6.9, 7.0, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 8.0,
    ];

    protected static array $modelCache = [];

    protected static function getContinuations(array $wall): array
    {
        if (empty($wall['continueSameArt'])) {
            return [];
        }

        $continuations = $wall['continuations'] ?? [];

        if (!is_array($continuations)) {
            return [];
        }

        return array_values(array_filter($continuations, function ($continuation) {
            return is_array($continuation);
        }));
    }

    public static function calculateWallArea(array $wall): float
    {
        $width = (float) ($wall['width'] ?? 0);
        $height = (float) ($wall['height'] ?? 0);
        $area = max($width, 0) * max($height, 0);

        foreach (self::getContinuations($wall) as $continuation) {
            $continuationWidth = max((float) ($continuation['width'] ?? 0), 0);
            $continuationHeight = max((float) ($continuation['height'] ?? 0), 0);
            $area += $continuationWidth * $continuationHeight;
        }

        return round($area, 2);
    }

    public static function calculateStripHeight(array $wall): ?float
    {
        $heights = [(float) ($wall['height'] ?? 0)];

        foreach (self::getContinuations($wall) as $continuation) {
            $heights[] = (float) ($continuation['height'] ?? 0);
        }

        $height = max($heights);

        if ($height <= 0) {
            return null;
        }

        foreach (self::STRIP_HEIGHT_OPTIONS as $option) {
            if ($option >= $height + 0.09) {
                return $option;
            }
        }

        return null;
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

        if ($stripHeight !== null && $stripHeight >= 6 && $numberOfStrips % 2 !== 0) {
            $numberOfStrips += 1;
        }

        return $numberOfStrips;
    }

    public static function calculateTotalArea(array $rooms): float
    {
        $total = 0;

        foreach ($rooms as $room) {
            foreach ($room['walls'] ?? [] as $wall) {
                $total += self::calculateWallArea($wall);
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

    public static function calculateTotalAmount(
        float $totalArea,
        array $rooms,
        ?array $selectedCarrier,
        ?string $paymentMethod
    ): float {
        self::$modelCache = [];

        $subtotal = $totalArea * self::PRICE_PER_SQUARE_METER;
        $subtotal += self::calculateModelCost($rooms);

        if ($selectedCarrier !== null) {
            $subtotal += (float) ($selectedCarrier['price'] ?? 0);
        }

        if ($paymentMethod === 'pix') {
            $subtotal *= 0.95;
        }

        return round($subtotal, 2);
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
}

