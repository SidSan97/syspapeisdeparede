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

    /**
     * Valores numéricos por ambiente (para inputs editáveis no preview).
     *
     * @return array<int, array{id: int, title: string, total: float, installment_total: float}>
     */
    public function itemAmounts(): array
    {
        return $this->budget->rooms
            ->values()
            ->map(fn (BudgetRoom $room, int $index) => $this->mapItemAmounts($room, $index))
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

        $totalRooms = $rooms->count();
        $totalWalls = $rooms->sum(fn (BudgetRoom $room) => $room->walls->count());
        $totalMeters = $rooms->sum(fn (BudgetRoom $room) => BudgetCalculator::calculateRoomMeters($room));
        $shipping = round((float) ($this->budget->selected_carrier_price ?? 0), 2);

        // Markup só sobre produtos (total base − frete); frete entra de novo sem markup.
        $totalInCash = $this->resolveMarkedTotal(
            'total_amount',
            (float) ($this->budget->total_amount ?? 0),
            $shipping,
        );

        $baseInstallment = (float) ($this->budget->total_amount_installments ?? 0);
        $totalInInstallments = null;
        if ($baseInstallment > 0 || $this->hasNumericOverride('total_amount_installments')) {
            $resolvedInstallment = $this->resolveMarkedTotal(
                'total_amount_installments',
                $baseInstallment,
                $shipping,
            );
            $totalInInstallments = $resolvedInstallment > 0 ? $resolvedInstallment : null;
        }

        return [
            'rooms' => $totalRooms,
            'walls' => $totalWalls,
            'meters' => round((float) $totalMeters, 2),
            'shipping' => $shipping,
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
        $amounts = $this->mapItemAmounts($room, $index);
        $walls = $room->walls;

        return [
            'title' => $amounts['title'],
            'description' => $this->wallLines($walls),
            'model_name' => $this->wallModelNames($walls),
            'meters' => number_format(BudgetCalculator::calculateRoomMeters($room), 2, ',', '.'),
            'total' => money_view($amounts['total']),
            'installment_total' => money_view($amounts['installment_total']),
        ];
    }

    /**
     * @return array{id: int, title: string, total: float, installment_total: float}
     */
    private function mapItemAmounts(BudgetRoom $room, int $index): array
    {
        $markup = $this->markup();
        $override = $this->itemOverride($room, $index);

        $total = array_key_exists('total', $override)
            ? round((float) $override['total'], 2)
            : round(BudgetCalculator::calculateRoomPriceVista($room) * $markup, 2);

        $installmentTotal = array_key_exists('installment_total', $override)
            ? round((float) $override['installment_total'], 2)
            : round(BudgetCalculator::calculateRoomPricePrazo($room) * $markup, 2);

        return [
            'id' => (int) $room->id,
            'title' => $room->name ?: ('Ambiente '.($index + 1)),
            'total' => $total,
            'installment_total' => $installmentTotal,
        ];
    }

    /**
     * @return array{total?: mixed, installment_total?: mixed}
     */
    private function itemOverride(BudgetRoom $room, int $index): array
    {
        $items = $this->overrides['items'] ?? null;

        if (! is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($item['id']) && (int) $item['id'] === (int) $room->id) {
                return $item;
            }
        }

        if (isset($items[$index]) && is_array($items[$index])) {
            return $items[$index];
        }

        return [];
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
     * Fator multiplicador de markup. Fallback padrão da proposta: 2.1
     */
    private function markup(): float
    {
        $defaultMarkup = 2.1;
        $markup = (float) ($this->overrides['mockup_percentage'] ?? $this->overrides['percentage'] ?? $defaultMarkup);

        return $markup > 0 ? $markup : $defaultMarkup;
    }

    private function hasNumericOverride(string $key): bool
    {
        return array_key_exists($key, $this->overrides)
            && $this->overrides[$key] !== null
            && $this->overrides[$key] !== '';
    }

    private function resolveMarkedTotal(string $overrideKey, float $baseAmount, float $shipping = 0.0): float
    {
        if ($this->hasNumericOverride($overrideKey)) {
            return round((float) $this->overrides[$overrideKey], 2);
        }

        $products = max(0.0, $baseAmount - $shipping);

        return round(($products * $this->markup()) + $shipping, 2);
    }
}
