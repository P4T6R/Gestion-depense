<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAndTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrator_can_view_the_audit_log(): void
    {
        $admin = User::factory()->admin()->create(['must_change_password' => false]);
        $accountant = User::factory()->comptable()->create(['must_change_password' => false]);
        AuditLog::create(['event' => 'test', 'description' => 'Événement de test']);

        $this->actingAs($accountant)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('audit.index'))->assertOk()->assertSee('Événement de test');
    }

    public function test_login_requires_totp_when_enabled(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $user = User::factory()->admin()->create([
            'password' => 'Secret!Password2',
            'must_change_password' => false,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret!Password2'])
            ->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $code = app(TotpService::class)->code($secret);
        $this->post(route('two-factor.challenge.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_chef_cannot_enable_two_factor_from_privileged_settings(): void
    {
        $chef = User::factory()->chefChantier()->create(['must_change_password' => false]);

        $this->actingAs($chef)->get(route('two-factor.settings'))->assertForbidden();
    }

    public function test_api_login_also_enforces_totp(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $user = User::factory()->comptable()->create([
            'password' => 'Secret!Password2',
            'must_change_password' => false,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Secret!Password2',
        ])->assertUnprocessable()->assertJsonPath('two_factor_required', true);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Secret!Password2',
            'two_factor_code' => app(TotpService::class)->code($secret),
        ])->assertOk()->assertJsonStructure(['token']);
    }
}
