<?php

namespace App\Services\Tiny;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TinyHttpClient
{
    private readonly string $token;

    private readonly string $baseUrl;

    public function __construct()
    {
        $this->token = (string) config('services.tiny_erp.token');
        $this->baseUrl = (string) config('services.tiny_erp.api_url');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->send('get', $endpoint, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $params = []): array
    {
        return $this->send('post', $endpoint, $params);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function encode(array $data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function send(string $method, string $endpoint, array $params): array
    {
        $query = http_build_query($this->withDefaults($params));

        $request = $this->request();

        if ($method === 'get') {
            $request = $request->retry(3, 200);
        }

        $response = $request->{$method}($endpoint.'?'.$query);

        $response->throw();

        return (array) $response->json('retorno', []);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->connectTimeout(10);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function withDefaults(array $params): array
    {
        return array_filter(
            array_merge([
                'token' => $this->token,
                'formato' => 'json',
            ], $params),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }
}
