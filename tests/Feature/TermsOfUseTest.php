<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TermsOfUseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_terms_of_use(): void
    {
        $this->getJson('/api/v1/terms-of-use')->assertUnauthorized();
    }

    public function test_authenticated_user_receives_default_budget_approval_term(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/terms-of-use')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'budget_approval')
            ->assertJsonPath('data.0.title', 'Termo de aprovação do orçamento')
            ->assertJsonPath('data.0.body', '');
    }

    public function test_authenticated_user_can_save_terms_of_use(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'terms' => [
                [
                    'id' => 'budget_approval',
                    'title' => 'Termo de aprovação do orçamento',
                    'body' => '<p>Parágrafo um.</p><p></p><p>Com <strong>negrito</strong> e <em>itálico</em>.</p>',
                ],
            ],
        ];

        $this->putJson('/api/v1/terms-of-use', $payload)
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Termo de aprovação do orçamento')
            ->assertJsonPath('data.0.body', $payload['terms'][0]['body']);

        $this->assertNotEmpty(Setting::get('terms_of_use'));

        $this->getJson('/api/v1/terms-of-use')
            ->assertOk()
            ->assertJsonPath('data.0.body', $payload['terms'][0]['body']);
    }

    public function test_html_is_sanitized_when_saving_terms(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/terms-of-use', [
            'terms' => [
                [
                    'id' => 'budget_approval',
                    'title' => 'Termo de aprovação do orçamento',
                    'body' => '<p>Ok</p><script>alert(1)</script><a href="https://evil.test">link</a>',
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.0.body', '<p>Ok</p>alert(1)link');
    }

    public function test_authenticated_user_can_save_multiple_terms(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'terms' => [
                [
                    'id' => 'budget_approval',
                    'title' => 'Termo de aprovação do orçamento',
                    'body' => '<p>Orçamento</p>',
                ],
                [
                    'id' => 'privacy',
                    'title' => 'Termo de privacidade',
                    'body' => '<p>Privacidade</p>',
                ],
            ],
        ];

        $this->putJson('/api/v1/terms-of-use', $payload)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.title', 'Termo de privacidade');
    }
}
