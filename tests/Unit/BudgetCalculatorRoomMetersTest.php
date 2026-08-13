<?php

namespace Tests\Unit;

use App\Support\Budget\BudgetCalculator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BudgetCalculatorRoomMetersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('tiny_erp_all_data', [
            'precoPromocionalVista' => 70.0,
            'precoPromocionalPrazo' => 114.9,
        ], now()->addHour());
    }

    public function test_room_meters_use_walls_sequence_not_independent_walls(): void
    {
        $walls = [
            [
                'width' => 4.2,
                'height' => 2.7,
                'continueSameArt' => false,
                'continuations' => [],
            ],
            [
                'width' => 3.0,
                'height' => 2.7,
                'continueSameArt' => true,
                'continuations' => [],
            ],
        ];

        $room = ['walls' => $walls];
        $sequence = BudgetCalculator::calculateWallsSequence($walls);

        $this->assertSame(
            round((float) $sequence['totalMetros'], 2),
            BudgetCalculator::calculateRoomMeters($room)
        );
    }

    public function test_sum_of_room_prices_plus_freight_matches_budget_total(): void
    {
        $rooms = [
            [
                'name' => 'Sala',
                'walls' => [
                    [
                        'width' => 4.2,
                        'height' => 2.7,
                        'continueSameArt' => false,
                        'continuations' => [],
                        'model' => null,
                    ],
                    [
                        'width' => 3.0,
                        'height' => 2.7,
                        'continueSameArt' => true,
                        'continuations' => [],
                        'model' => null,
                    ],
                ],
            ],
            [
                'name' => 'Quarto',
                'walls' => [
                    [
                        'width' => 2.5,
                        'height' => 2.7,
                        'continueSameArt' => false,
                        'continuations' => [],
                        'model' => null,
                    ],
                ],
            ],
        ];

        $carrier = ['name' => 'Transportadora', 'price' => 67.87, 'deliveryTime' => 5];
        $totalArea = BudgetCalculator::calculateTotalArea($rooms);
        $totalVista = BudgetCalculator::calculateTotalAmountVista($totalArea, $rooms, $carrier);

        $roomsSum = 0.0;
        foreach ($rooms as $room) {
            $roomsSum += BudgetCalculator::calculateRoomPriceVista($room);
        }

        $this->assertEqualsWithDelta($totalVista, round($roomsSum + 67.87, 2), 0.01);
        $this->assertEqualsWithDelta($totalArea, round(array_sum(array_map(
            fn (array $room) => BudgetCalculator::calculateRoomMeters($room),
            $rooms
        )), 2), 0.01);
    }
}
