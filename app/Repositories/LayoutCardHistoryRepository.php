<?php

namespace App\Repositories;

use App\Models\LayoutCardHistory;

class LayoutCardHistoryRepository
{
    protected $layoutCardHistory;

    public function __construct(LayoutCardHistory $layoutCardHistory)
    {
        $this->layoutCardHistory = $layoutCardHistory;
    }

    /**
     * Create a new history entry
     *
     * @param int $cardId
     * @param string $description
     * @param string|null $typePage
     * @return LayoutCardHistory
     */
    public function create(int $cardId, string $description, ?string $typePage = null): LayoutCardHistory
    {
        return $this->layoutCardHistory::create([
            'card_id' => $cardId,
            'description' => $description,
            'type_page' => $typePage,
        ]);
    }

    /**
     * Get history for a specific card
     *
     * @param int $cardId
     * @param string|null $typePage
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCardId(int $cardId, ?string $typePage = null)
    {
        $query = $this->layoutCardHistory::where('card_id', $cardId);

        if ($typePage) {
            $query->where('type_page', $typePage);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}

