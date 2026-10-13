<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileChefAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_accepts_only_active_site_managers(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $directeur = User::factory()->create(['role' => 'directeur']);
        $inactive = User::factory()->chefChantier()->create(['is_active' => false]);

        $this->postJson('/api/mobile/login', ['email' => $chef->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.role', 'chef_chantier')->assertJsonStructure(['token']);
        $this->postJson('/api/mobile/login', ['email' => $directeur->email, 'password' => 'password'])
            ->assertForbidden();
        $this->postJson('/api/mobile/login', ['email' => $inactive->email, 'password' => 'password'])
            ->assertForbidden();
    }

    public function test_mobile_login_does_not_revoke_another_device(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $firstToken = $chef->createToken('mobile_chef')->plainTextToken;

        $this->postJson('/api/mobile/login', ['email' => $chef->email, 'password' => 'password'])
            ->assertOk();

        $this->withToken($firstToken)->getJson('/api/auth/me')
            ->assertOk()->assertJsonPath('user.id', $chef->id);
    }
}
