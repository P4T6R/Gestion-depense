<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FirstLoginPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_admin_created_user_must_change_their_temporary_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nouvel utilisateur',
            'email' => 'nouveau@example.com',
            'password' => 'Temporaire123!',
            'password_confirmation' => 'Temporaire123!',
            'role' => 'comptable',
            'phone' => null,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau@example.com',
            'must_change_password' => true,
        ]);
    }

    public function test_admin_password_reset_marks_the_password_as_temporary(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'must_change_password' => false,
            'remember_token' => 'ancien-jeton',
        ]);

        $this->actingAs($admin)->put(route('users.update-password', $user), [
            'password' => 'NouveauTemp123!',
            'password_confirmation' => 'NouveauTemp123!',
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertNotSame('ancien-jeton', $user->getRememberToken());
    }

    public function test_first_web_login_is_redirected_to_the_required_password_change(): void
    {
        User::factory()->create([
            'email' => 'premiere.connexion@example.com',
            'password' => Hash::make('Temporaire123!'),
            'must_change_password' => true,
        ]);

        $this->post('/login', [
            'email' => 'premiere.connexion@example.com',
            'password' => 'Temporaire123!',
        ])->assertRedirect(route('password.change.required'));
    }

    public function test_user_with_temporary_password_cannot_access_business_pages(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change.required'));

        $this->actingAs($user)
            ->get(route('password.change.required'))
            ->assertOk();
    }

    public function test_user_can_replace_the_temporary_password_and_continue(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Temporaire123!'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->put(route('password.change.required.update'), [
            'current_password' => 'Temporaire123!',
            'password' => 'Personnel456!',
            'password_confirmation' => 'Personnel456!',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('Personnel456!', $user->password));

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_current_password_is_required_to_replace_the_temporary_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Temporaire123!'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->from(route('password.change.required'))->put(route('password.change.required.update'), [
            'current_password' => 'MotDePasseIncorrect',
            'password' => 'Personnel456!',
            'password_confirmation' => 'Personnel456!',
        ])->assertRedirect(route('password.change.required'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_personal_password_must_meet_the_security_policy(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Temporaire123!'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->put(route('password.change.required.update'), [
            'current_password' => 'Temporaire123!',
            'password' => 'motdepassefaible',
            'password_confirmation' => 'motdepassefaible',
        ])->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_an_existing_browser_session_is_invalidated_after_a_password_reset(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('AncienMotDePasse123!'),
            'must_change_password' => false,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill([
            'password' => Hash::make('NouveauTemporaire123!'),
            'must_change_password' => true,
        ])->save();
        $this->app['auth']->forgetGuards();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_api_blocks_business_requests_until_password_is_changed(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'password_change_required');
    }

    public function test_api_password_change_clears_the_requirement(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Temporaire123!'),
            'must_change_password' => true,
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/change-password', [
            'current_password' => 'Temporaire123!',
            'new_password' => 'Personnel456!',
            'new_password_confirmation' => 'Personnel456!',
        ])->assertOk()
            ->assertJsonPath('password_change_required', false);

        $this->assertFalse($user->fresh()->must_change_password);
    }
}
