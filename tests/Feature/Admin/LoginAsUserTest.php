<?php

namespace Tests\Feature\Admin;

use App\Models\ImpersonationToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LoginAsUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::unguarded(function () {
            Role::query()->create(['id' => 1, 'name' => 'Admin', 'slug' => 'admin']);
            Role::query()->create(['id' => 2, 'name' => 'Seller', 'slug' => 'seller']);
        });

        config(['app.frontend_url' => 'https://front.example']);
    }

    public function test_admin_can_impersonate_user(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $seller = User::factory()->create(['role_id' => 2]);

        $response = $this->actingAs($admin)->get(route('admin.loginAs', $seller->id));

        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');
        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('https://front.example', $redirectUrl);

        $parts = parse_url($redirectUrl);
        parse_str($parts['query'] ?? '', $query);

        $this->assertArrayHasKey('impersonation_token', $query);
        $plainToken = $query['impersonation_token'];
        $tokenHash = hash('sha256', $plainToken);

        $this->assertDatabaseHas('impersonation_tokens', [
            'admin_id' => $admin->id,
            'user_id' => $seller->id,
            'token_hash' => $tokenHash,
        ]);

        $now = Carbon::parse('2025-01-01 12:00:00');
        Carbon::setTestNow($now);

        $exchange = $this->postJson(route('impersonation.exchange'), [
            'token' => $plainToken,
        ]);

        $exchange->assertOk();
        $exchange->assertJsonStructure([
            'token',
            'user' => ['id', 'name'],
        ]);

        $this->assertDatabaseHas('impersonation_tokens', [
            'token_hash' => $tokenHash,
            'used_at' => $now,
        ]);

        $this->assertNotNull(ImpersonationToken::query()->where('token_hash', $tokenHash)->value('used_ip'));

        $secondAttempt = $this->postJson(route('impersonation.exchange'), [
            'token' => $plainToken,
        ]);

        $secondAttempt->assertStatus(422);

        Carbon::setTestNow();
    }
}
