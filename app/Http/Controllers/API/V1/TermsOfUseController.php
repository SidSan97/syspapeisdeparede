<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTermsOfUseRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TermsOfUseController extends Controller
{
    public const SETTING_KEY = 'terms_of_use';

    public function index(): JsonResponse
    {
        $cached = Cache::get(self::SETTING_KEY);

        if (is_string($cached) && $cached !== '' || $cached !== null) {
            return response()->json([
                'data' => json_decode($cached, true),
            ]);
        }

        Cache::put(self::SETTING_KEY, json_encode($this->getTerms(), JSON_UNESCAPED_UNICODE), now()->addHours(24));

        return response()->json([
            'data' => $this->getTerms(),
        ]);
    }

    public function store(UpdateTermsOfUseRequest $request): JsonResponse
    {
        $terms = collect($request->validated('terms'))
            ->map(function (array $term) {
                $body = $this->sanitizeHtml((string) ($term['body'] ?? ''));

                return [
                    'id' => (string) ($term['id'] ?: Str::slug($term['title'])),
                    'title' => trim((string) $term['title']),
                    'body' => $body,
                ];
            })
            ->values()
            ->all();

        // Setting::set remove a chave se o valor for vazio; JSON sempre não-vazio.
        Setting::set(self::SETTING_KEY, json_encode($terms, JSON_UNESCAPED_UNICODE), 'string');
        Setting::flushCache();

        Cache::forget(self::SETTING_KEY);

        return response()->json([
            'message' => 'Termos de uso salvos com sucesso.',
            'data' => $terms,
        ]);
    }

    /**
     * @return list<array{id: string, title: string, body: string}>
     */
    protected function getTerms(): array
    {
        $raw = Setting::get(self::SETTING_KEY);

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && $decoded !== []) {
                return array_values(array_map(function ($term) {
                    return [
                        'id' => (string) ($term['id'] ?? 'term'),
                        'title' => (string) ($term['title'] ?? ''),
                        'body' => (string) ($term['body'] ?? ''),
                    ];
                }, $decoded));
            }
        }

        return [$this->defaultTerm()];
    }

    /**
     * @return array{id: string, title: string, body: string}
     */
    protected function defaultTerm(): array
    {
        return [
            'id' => 'budget_approval',
            'title' => 'Termo de aprovação do orçamento',
            'body' => '',
        ];
    }

    protected function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><em><b><i>';

        return trim(strip_tags($html, $allowed));
    }
}
