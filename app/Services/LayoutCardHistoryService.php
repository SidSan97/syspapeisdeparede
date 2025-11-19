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
     * @return void
     */
    public function logDescriptionChange(int $cardId, User $user): void
    {
        $description = "{$user->name} alterou a descrição do card.";
        $this->historyRepository->create($cardId, $description);
    }

    /**
     * Log file attachment
     *
     * @param int $cardId
     * @param User $user
     * @param string $fileName
     * @param string|null $fileUrl
     * @return void
     */
    public function logFileAttachment(int $cardId, User $user, string $fileName, ?string $fileUrl = null): void
    {
        if ($fileUrl) {
            $description = "{$user->name} adicionou um novo anexo: <a href=\"{$fileUrl}\" target=\"_blank\">{$fileName}</a>";
        } else {
            $description = "{$user->name} adicionou um novo anexo: {$fileName}";
        }
        $this->historyRepository->create($cardId, $description);
    }

    /**
     * Log member join
     *
     * @param int $cardId
     * @param User $user
     * @param User|null $memberUser
     * @return void
     */
    public function logMemberJoin(int $cardId, User $user, ?User $memberUser = null): void
    {
        $memberName = $memberUser ? $memberUser->name : $user->name;
        $description = "{$memberName} ingressou no card.";
        $this->historyRepository->create($cardId, $description);
    }

    /**
     * Log member removal
     *
     * @param int $cardId
     * @param User $user
     * @param User $removedUser
     * @return void
     */
    public function logMemberRemoval(int $cardId, User $user, User $removedUser): void
    {
        $description = "{$user->name} removeu {$removedUser->name} do card.";
        $this->historyRepository->create($cardId, $description);
    }

    /**
     * Log member leave
     *
     * @param int $cardId
     * @param User $user
     * @return void
     */
    public function logMemberLeave(int $cardId, User $user): void
    {
        $description = "{$user->name} saiu do card.";
        $this->historyRepository->create($cardId, $description);
    }

    /**
     * Log column change
     *
     * @param int $cardId
     * @param User $user
     * @param LayoutColumnName $newColumn
     * @return void
     */
    public function logColumnChange(int $cardId, User $user, LayoutColumnName $newColumn): void
    {
        $description = "{$user->name} moveu o card para a coluna \"{$newColumn->name}\".";
        $this->historyRepository->create($cardId, $description);
    }
}

