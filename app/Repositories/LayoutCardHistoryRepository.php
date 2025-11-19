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
     * @return LayoutCardHistory
     */
    public function create(int $cardId, string $description): LayoutCardHistory
    {
        return $this->layoutCardHistory::create([
            'card_id' => $cardId,
            'description' => $description,
        ]);
    }

    /**
     * Get history for a specific card
     *
     * @param int $cardId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCardId(int $cardId)
    {
        return $this->layoutCardHistory::where('card_id', $cardId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

