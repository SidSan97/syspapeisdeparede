<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\BudgetWall;
use App\Models\Reseller;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BudgetViewModel
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public function __construct(
        public Budget $budget,
        public array $overrides = [],
    ) {}

    /**
     * @return array<int, array{title: string, description: string, model_name: string, meters: string, total: string, installment_total: string}>
     */
    public function items(): array
    {
        return $this->budget->rooms
            ->values()
            ->map(fn (BudgetRoom $room, int $index) => $this->mapItem($room, $index))
            ->all();
    }

    public function estimatedDelivery(): ?string
    {
        $deliveryTime = $this->budget->delivery_time;

        if (! $deliveryTime) {
            return 'Não informado';
        }

        $days = (int) $deliveryTime;

        return $days.' '.($days === 1 ? 'dia' : 'dias');
    }

    /**
     * @return array{rooms: int, walls: int, meters: float, shipping: float, total_in_cash: float, total_in_installments: ?float, total: float}
     */
    public function totals(): array
    {
        $rooms = $this->budget->rooms;
        $markup = $this->markup();

        $totalRooms = $rooms->count();
        $totalWalls = $rooms->sum(fn (BudgetRoom $room) => $room->walls->count());
        $totalMeters = $rooms->sum(fn (BudgetRoom $room) => BudgetCalculator::calculateRoomMeters($room));
        $shipping = (float) ($this->budget->selected_carrier_price ?? 0);

        // Mesma regra do Vue: base do orçamento × markup (ou totais já finais via override).
        $totalInCash = $this->resolveMarkedTotal(
            'total_amount',
            (float) ($this->budget->total_amount ?? 0),
        );

        $baseInstallment = (float) ($this->budget->total_amount_installments ?? 0);
        $totalInInstallments = null;
        if ($baseInstallment > 0 || $this->hasNumericOverride('total_amount_installments')) {
            $resolvedInstallment = $this->resolveMarkedTotal('total_amount_installments', $baseInstallment);
            $totalInInstallments = $resolvedInstallment > 0 ? $resolvedInstallment : null;
        }

        return [
            'rooms' => $totalRooms,
            'walls' => $totalWalls,
            'meters' => round((float) $totalMeters, 2),
            'shipping' => round($shipping * $markup, 2),
            'total_in_cash' => $totalInCash,
            'total_in_installments' => $totalInInstallments,
            'total' => $totalInCash,
        ];
    }

    public function carrier(): ?string
    {
        $carrierName = $this->budget->selected_carrier_name;

        if (! $carrierName) {
            return null;
        }

        if (str_contains($carrierName, ' - ')) {
            return trim(explode(' - ', $carrierName)[0]);
        }

        return trim($carrierName);
    }

    public function notes(): ?string
    {
        return $this->overrides['notes'] ?? null;
    }

    /**
     * @return array{name: string, document: ?string, address_line_1: ?string, address_line_2: ?string, phone: ?string, email: ?string, logo_base64: ?string}|null
     */
    public function dealer(): ?array
    {
        $reseller = $this->budget->tenant?->reseller;

        if (! $reseller) {
            return null;
        }

        return [
            'name' => $reseller->fantasy_name ?: ($reseller->name ?: '—'),
            'document' => $reseller->cnpj,
            'address_line_1' => $this->formatAddressLine1($reseller),
            'address_line_2' => $this->formatAddressLine2($reseller),
            'phone' => $reseller->phone ? $this->formatPhone($reseller->phone) : null,
            'email' => $reseller->email,
            'logo_base64' => $this->logoBase64($this->budget->tenant?->avatar),
        ];
    }

    private function logoBase64(?string $avatarPath): ?string
    {
        if (! $avatarPath || ! Storage::disk('public')->exists($avatarPath)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($avatarPath) ?: 'image/png';
        $contents = Storage::disk('public')->get($avatarPath);

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    /**
     * @return array{title: string, description: string, model_name: string, meters: string, total: string, installment_total: string}
     */
    private function mapItem(BudgetRoom $room, int $index): array
    {
        $walls = $room->walls;
        $markup = $this->markup();

        return [
            'title' => $room->name ?: ('Ambiente '.($index + 1)),
            'description' => $this->wallLines($walls),
            'model_name' => $this->wallModelNames($walls),
            'meters' => number_format(BudgetCalculator::calculateRoomMeters($room), 2, ',', '.'),
            'total' => money_view($this->overrides['total_amount']),
            'installment_total' => money_view($this->overrides['total_amount_installments']),
        ];
    }

    /**
     * @param  Collection<int, BudgetWall>  $walls
     */
    private function wallLines(Collection $walls): string
    {
        return $walls
            ->values()
            ->map(fn (BudgetWall $wall, int $index) => '<div class="wall-summary">'.$this->formatWallLine($wall, $index).'</div>')
            ->implode('');
    }

    /**
     * @param  Collection<int, BudgetWall>  $walls
     */
    private function wallModelNames(Collection $walls): string
    {
        return $walls
            ->map(fn (BudgetWall $wall) => '<div class="wall-summary">'.e($wall->collectionModel->name ?? '—').'</div>')
            ->implode('');
    }

    private function formatWallLine(BudgetWall $wall, int $wallIndex): string
    {
        $wallData = BudgetCalculator::normalizeWallForCalculation($wall);
        $sequence = BudgetCalculator::calculateWallWithContinuations($wallData);
        $stripSummary = BudgetCalculator::formatStripGroups($sequence['groups'] ?? []);

        $label = $wall->name ?: ('Parede '.($wallIndex + 1));
        $parts = [$label];

        if ($wall->width && $wall->height) {
            $parts[] = number_format((float) $wall->width, 2, ',', '.').'m x '.number_format((float) $wall->height, 2, ',', '.').'m';
        }

        $line = implode(' | ', $parts);
        if ($stripSummary) {
            $line .= ' — '.$stripSummary;
        }

        $continuations = $this->formatContinuations($wall);
        if (count($continuations) > 0) {
            $line .= '<br><span style="font-size:11px;">'.implode('<br>', $continuations).'</span>';
        }

        return $line;
    }

    /**
     * @return array<int, string>
     */
    private function formatContinuations(BudgetWall $wall): array
    {
        if (empty($wall->continue_same_art) || ! is_array($wall->continuations)) {
            return [];
        }

        $continuations = [];

        foreach ($wall->continuations as $continuationIndex => $continuation) {
            if (! is_array($continuation)) {
                continue;
            }

            $continuationParts = [];
            $continuationName = trim((string) ($continuation['name'] ?? ''));
            $continuationParts[] = $continuationName !== '' ? $continuationName : ('Continuação '.($continuationIndex + 1));

            $continuationWidth = (float) ($continuation['width'] ?? 0);
            $continuationHeight = (float) ($continuation['height'] ?? 0);
            if ($continuationWidth > 0 && $continuationHeight > 0) {
                $continuationParts[] = number_format($continuationWidth, 2, ',', '.').'m x '.number_format($continuationHeight, 2, ',', '.').'m';
            }

            $continuations[] = '+ '.implode(' | ', $continuationParts);
        }

        return $continuations;
    }

    private function formatPhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (strlen($cleaned) === 10) {
            return '('.substr($cleaned, 0, 2).') '.substr($cleaned, 2, 4).'-'.substr($cleaned, 6);
        }

        if (strlen($cleaned) === 11) {
            return '('.substr($cleaned, 0, 2).') '.substr($cleaned, 2, 5).'-'.substr($cleaned, 7);
        }

        return $phone;
    }

    private function formatAddressLine1(Reseller $reseller): ?string
    {
        $parts = [];

        if ($reseller->public_space) {
            $parts[] = $reseller->public_space;
        }
        if ($reseller->number) {
            $parts[] = 'Nº '.$reseller->number;
        }
        if ($reseller->complement) {
            $parts[] = $reseller->complement;
        }
        if ($reseller->neighborhood) {
            $parts[] = 'Bairro: '.$reseller->neighborhood;
        }

        return count($parts) > 0 ? implode('. ', $parts) : null;
    }

    private function formatAddressLine2(Reseller $reseller): ?string
    {
        $parts = [];

        if ($reseller->cep) {
            $cep = preg_replace('/\D/', '', $reseller->cep);
            $parts[] = strlen($cep) === 8 ? substr($cep, 0, 5).'-'.substr($cep, 5) : $reseller->cep;
        }
        if ($reseller->city && $reseller->uf) {
            $parts[] = $reseller->city.', '.$reseller->uf;
        }

        return count($parts) > 0 ? implode(' - ', $parts) : null;
    }

    /**
     * Fator multiplicador de markup informado pelo usuário (mín. matemático de 1, ou seja, sem markup).
     */
    private function markup(): float
    {
        $markup = (float) ($this->overrides['mockup_percentage'] ?? $this->overrides['percentage'] ?? 1);

        return $markup > 0 ? $markup : 1.0;
    }

    private function hasNumericOverride(string $key): bool
    {
        return array_key_exists($key, $this->overrides)
            && $this->overrides[$key] !== null
            && $this->overrides[$key] !== '';
    }

    private function resolveMarkedTotal(string $overrideKey, float $baseAmount): float
    {
        if ($this->hasNumericOverride($overrideKey)) {
            return round((float) $this->overrides[$overrideKey], 2);
        }

        return round($baseAmount * $this->markup(), 2);
    }
}
