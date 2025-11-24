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

    /**
     * Log description change
     *
     * @param int $cardId
     * @param User $user
     * @param string|null $typePage
     * @return void
     */
    public function logDescriptionChange(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} alterou a descrição do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    /**
     * Log file attachment
     *
     * @param int $cardId
     * @param User $user
     * @param string $fileName
     * @param string|null $fileUrl
     * @param string|null $typePage
     * @return void
     */
    public function logFileAttachment(int $cardId, User $user, string $fileName, ?string $fileUrl = null, ?string $typePage = null): void
    {
        if ($fileUrl) {
            $description = "{$user->name} adicionou um novo anexo: <a href=\"{$fileUrl}\" target=\"_blank\">{$fileName}</a>";
        } else {
            $description = "{$user->name} adicionou um novo anexo: {$fileName}";
        }
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    /**
     * Log member join
     *
     * @param int $cardId
     * @param User $user
     * @param User|null $memberUser
     * @param string|null $typePage
     * @return void
     */
    public function logMemberJoin(int $cardId, User $user, ?User $memberUser = null, ?string $typePage = null): void
    {
        $memberName = $memberUser ? $memberUser->name : $user->name;
        $description = "{$memberName} ingressou no card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    /**
     * Log member removal
     *
     * @param int $cardId
     * @param User $user
     * @param User $removedUser
     * @param string|null $typePage
     * @return void
     */
    public function logMemberRemoval(int $cardId, User $user, User $removedUser, ?string $typePage = null): void
    {
        $description = "{$user->name} removeu {$removedUser->name} do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    /**
     * Log member leave
     *
     * @param int $cardId
     * @param User $user
     * @param string|null $typePage
     * @return void
     */
    public function logMemberLeave(int $cardId, User $user, ?string $typePage = null): void
    {
        $description = "{$user->name} saiu do card.";
        $this->historyRepository->create($cardId, $description, $typePage);
    }

    /**
     * Log column change
     *
     * @param int $cardId
     * @param User $user
     * @param int $columnId
     * @param string|null $typePage
     * @return void
     */
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
}

