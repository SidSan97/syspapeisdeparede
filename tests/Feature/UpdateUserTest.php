<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    public function test_guest_cannot_update_user(): void
    {
        $user = User::factory()->role(UserRole::Designer)->create();

        $this->putJson("/api/v1/users/{$user->id}", $this->payload($user))
            ->assertUnauthorized();
    }

    public function test_password_is_unchanged_when_omitted(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $user = User::factory()->role(UserRole::Designer)->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/users/{$user->id}", $this->payload($user, [
            'name' => 'Nome atualizado',
        ]))->assertOk();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertSame('Nome atualizado', $user->fresh()->name);
    }

    public function test_password_is_unchanged_when_blank(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $user = User::factory()->role(UserRole::Designer)->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/users/{$user->id}", $this->payload($user, [
            'password' => '',
            'password_confirmation' => '',
        ]))->assertOk();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_is_updated_when_confirmed(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $user = User::factory()->role(UserRole::Designer)->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/users/{$user->id}", $this->payload($user, [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]))->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_update_requires_confirmation(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $user = User::factory()->role(UserRole::Designer)->create([
            'password' => 'password',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/users/{$user->id}", $this->payload($user, [
            'password' => 'new-password-123',
            'password_confirmation' => 'does-not-match',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'A confirmação da senha não confere.');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name ?? UserRole::Designer->value,
        ], $overrides);
    }
}
