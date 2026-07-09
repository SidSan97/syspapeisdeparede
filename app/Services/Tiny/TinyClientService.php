<?php

namespace App\Services\Tiny;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TinyClientService
{
    private const CONTACTS_ENDPOINT = 'contatos.pesquisa.php';

    private readonly string $token;

    private readonly string $baseUrl;

    public function __construct()
    {
        $this->token = (string) config('services.tiny_erp.token');
        $this->baseUrl = (string) config('services.tiny_erp.api_url');
    }

    /**
     * Search a single page of contacts on the Tiny ERP API.
     *
     * @return array<string, mixed> The raw "retorno" payload from Tiny.
     *
     * @throws ConnectionException|RequestException
     */
    public function searchContacts(?string $search = null, int $page = 1): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->retry(3, 200)
            ->get(self::CONTACTS_ENDPOINT, array_filter([
                'token' => $this->token,
                'formato' => 'json',
                'pesquisa' => $search,
                'pagina' => $page,
            ], fn (mixed $value): bool => $value !== null));

        $response->throw();

        return (array) $response->json('retorno', []);
    }

    /**
     * Fetch every page of contacts from the Tiny ERP API.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws RuntimeException When Tiny responds with a status other than "OK".
     */
    public function getAllContacts(?string $search = null): Collection
    {
        $contacts = collect();
        $page = 1;
        $totalPages = 1;

        do {
            $retorno = $this->searchContacts($search, $page);

            if (($retorno['status'] ?? null) !== 'OK') {
                throw new RuntimeException(
                    'Tiny ERP retornou status inesperado ao pesquisar contatos: '.($retorno['status'] ?? 'desconhecido')
                );
            }

            $totalPages = (int) ($retorno['numero_paginas'] ?? 1);

            foreach ($retorno['contatos'] ?? [] as $item) {
                if (isset($item['contato'])) {
                    $contacts->push($item['contato']);
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return $contacts;
    }
}
