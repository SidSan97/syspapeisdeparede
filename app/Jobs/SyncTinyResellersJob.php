<?php

namespace App\Jobs;

use App\Models\Reseller;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\Tiny\TinyClientService;
use App\Support\UserType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncTinyResellersJob implements ShouldQueue
{
    use Queueable;

    private const SYNC_TYPE = 'tiny_resellers';

    private const PJ_PERSON_TYPE = 'J';

    private const ACTIVE_STATUS = 'Ativo';

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function handle(TinyClientService $tinyClient): void
    {
        $syncLog = SyncLog::create([
            'type' => self::SYNC_TYPE,
            'status' => 'running',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $processed = $this->syncResellers($tinyClient);

            $syncLog->update([
                'status' => 'success',
                'records_processed' => $processed,
                'finished_at' => now(),
            ]);

            Log::info('[SyncTinyResellersJob] Sincronização concluída', ['records_processed' => $processed]);
        } catch (Throwable $e) {
            $syncLog->update([
                'status' => 'error',
                'message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            Log::error('[SyncTinyResellersJob] Falha na sincronização de revendedores', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function syncResellers(TinyClientService $tinyClient): int
    {
        $contacts = $tinyClient->getAllContacts();

        $processed = 0;

        foreach ($contacts as $contact) {
            if (($contact['tipo_pessoa'] ?? null) !== self::PJ_PERSON_TYPE) {
                continue;
            }

            $reseller = Reseller::updateOrCreate(
                ['tiny_id' => $contact['id']],
                [
                    'tiny_code' => $contact['codigo'] ?? null,
                    'name' => $contact['nome'],
                    'fantasy_name' => $contact['fantasia'] ?? null,
                    'cnpj' => $contact['cpf_cnpj'] ?? null,
                    'site' => $contact['site'] ?? null,
                    'ie' => $contact['ie'] ?? null,
                    'person_type' => $contact['tipo_pessoa'],
                    'email' => $contact['email'] ?? null,
                    'phone' => $contact['fone'] ?? null,
                    'cep' => $contact['cep'] ?? null,
                    'uf' => $contact['uf'] ?? null,
                    'city' => $contact['cidade'] ?? null,
                    'neighborhood' => $contact['bairro'] ?? null,
                    'public_space' => $contact['endereco'] ?? null,
                    'number' => $contact['numero'] ?? null,
                    'complement' => $contact['complemento'] ?? null,
                    'status' => $contact['situacao'] ?? null,
                    'last_sync_at' => now(),
                ]
            );

            $this->provisionUserAccount($reseller);

            $processed++;
        }

        return $processed;
    }

    /**
     * Cria automaticamente o acesso do revendedor, caso ele ainda não exista.
     *
     * O usuário é criado com uma senha aleatória e inutilizável: o acesso real
     * só é obtido posteriormente pelo fluxo de "esqueci minha senha".
     */
    private function provisionUserAccount(Reseller $reseller): void
    {
        if ($reseller->status !== self::ACTIVE_STATUS) {
            return;
        }

        if (empty($reseller->email)) {
            Log::warning('[SyncTinyResellersJob] Revendedor sem e-mail, usuário não criado', [
                'reseller_id' => $reseller->id,
                'tiny_id' => $reseller->tiny_id,
            ]);

            return;
        }

        if ($reseller->user()->exists()) {
            return;
        }

        if (User::where('email', $reseller->email)->exists()) {
            Log::warning('[SyncTinyResellersJob] E-mail já em uso por outro usuário, usuário não criado', [
                'reseller_id' => $reseller->id,
                'email' => $reseller->email,
            ]);

            return;
        }

        $user = User::create([
            'name' => $reseller->fantasy_name ?: $reseller->name,
            'email' => $reseller->email,
            'password' => Str::password(32),
            'reseller_id' => $reseller->id,
            'user_type_id' => UserType::RESELLER,
        ]);

        $user->assignRole('reseller');

        Log::info('[SyncTinyResellersJob] Usuário criado para revendedor', [
            'reseller_id' => $reseller->id,
            'user_id' => $user->id,
        ]);
    }
}
