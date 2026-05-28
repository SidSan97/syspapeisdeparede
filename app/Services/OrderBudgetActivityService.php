<?php

namespace App\Services;

use App\Models\OrderBudget;
use App\Models\OrderBudgetActivitySession;
use Illuminate\Support\Collection;

class OrderBudgetActivityService
{
    /**
     * Persiste a sessão em execução (pausa ou conclusão do card).
     */
    public function recordClosedSession(OrderBudget $orderBudget): ?OrderBudgetActivitySession
    {
        if (!$orderBudget->activity_running_since) {
            return null;
        }

        $endedAt = now();
        $startedAt = $orderBudget->activity_running_since;
        $durationSeconds = max(0, $endedAt->diffInSeconds($startedAt, true));

        return OrderBudgetActivitySession::query()->create([
            'order_budget_id' => $orderBudget->id,
            'tenant_id' => $orderBudget->tenant_id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ]);
    }

    /**
     * @return list<array{id: int, started_at: string, ended_at: string, duration_seconds: int}>
     */
    public function sessionsPayload(OrderBudget $orderBudget): array
    {
        $sessions = $orderBudget->relationLoaded('activitySessions')
            ? $orderBudget->activitySessions
            : $orderBudget->activitySessions()->orderBy('ended_at')->get();

        return $this->mapSessions($sessions);
    }

    /**
     * @param  Collection<int, OrderBudgetActivitySession>|iterable<OrderBudgetActivitySession>  $sessions
     * @return list<array{id: int, started_at: string, ended_at: string, duration_seconds: int}>
     */
    public function mapSessions(iterable $sessions): array
    {
        $mapped = [];

        foreach ($sessions as $session) {
            $mapped[] = [
                'id' => $session->id,
                'started_at' => $session->started_at->toIso8601String(),
                'ended_at' => $session->ended_at->toIso8601String(),
                'duration_seconds' => (int) $session->duration_seconds,
            ];
        }

        return $mapped;
    }

    public function deleteSessionsForCard(int $orderBudgetId): void
    {
        OrderBudgetActivitySession::query()
            ->where('order_budget_id', $orderBudgetId)
            ->delete();
    }
}
