<?php

namespace App\Services;

use App\Models\LayoutColumnName;
use App\Models\User;
use App\Repositories\LayoutCardHistoryRepository;

class LayoutCardHistoryService
{
    protected $historyRepository;

    public function __construct(LayoutCardHistoryRepository $historyRepository)
    {
        $this->historyRepository = $historyRepository;
    }

    public function logDescriptionChange(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} alterou a descrição do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logFileAttachment(int $cardId, User $user, string $fileName, ?string $fileUrl = null, ?string $typePage = null): void
    {
        if ($fileUrl) {
            $description = "{$user->name} adicionou um novo anexo: <a href=\"{$fileUrl}\" target=\"_blank\">{$fileName}</a>";
        } else {
            $description = "{$user->name} adicionou um novo anexo: {$fileName}";
        }
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logMemberJoin(int $cardId, User $user, ?User $memberUser = null, ?string $typePage = null): void
    {
        $memberName = $memberUser ? $memberUser->name : $user->name;
        $description = "{$memberName} ingressou no card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logMemberRemoval(int $cardId, User $user, User $removedUser, ?string $typePage = null): void
    {
        $description = "{$user->name} removeu {$removedUser->name} do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logMemberLeave(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} saiu do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logColumnChange(int $cardId, User $user, int $columnId, ?string $typePage = null): void
    {
        // Buscar o nome da coluna dinamicamente baseado no type_page
        $columnName = 'Coluna';

        if ($typePage === 'product') {
            $column = \App\Models\ProductionColumnName::find($columnId);
            if ($column) {
                $columnName = $column->name;
            }
        } else {
            // Default para layout
            $column = \App\Models\LayoutColumnName::find($columnId);
            if ($column) {
                $columnName = $column->name;
            }
        }

        $description = "{$user->name} moveu o card para a coluna \"{$columnName}\".";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logProductionDateUpdate(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} marcou o card como produzido.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logProductionPercentageUpdate(int $cardId, User $user, float $percentage, ?string $typePage = null): void
    {
        $description = "{$user->name} atualizou a porcentagem de produção para {$percentage}%.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logActivityStarted(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} iniciou o temporizador do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    public function logActivityPaused(int $cardId, User $user, int $durationSeconds, ?string $typePage = null): void
    {
        $label = $this->formatDurationLabel($durationSeconds);
        $description = "{$user->name} pausou o temporizador do card ({$label}).";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    protected function formatDurationLabel(int $durationSeconds): string
    {
        $safe = max(0, $durationSeconds);
        $minutes = intdiv($safe, 60);
        $seconds = $safe % 60;

        if ($minutes > 0) {
            return $seconds > 0 ? "{$minutes}m {$seconds}s" : "{$minutes}m";
        }

        return "{$seconds}s";
    }

    public function logCardCompleted(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} concluiu o card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }
}

