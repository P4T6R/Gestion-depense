<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_receives_a_queued_secure_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification): bool {
            return $notification->queue === 'mail';
        });
    }

    public function test_response_does_not_reveal_an_unknown_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'inconnu@example.test'])
            ->assertSessionHas('status', 'Si ce compte existe, un lien sécurisé lui a été envoyé.')
            ->assertSessionHasNoErrors();
    }

    public function test_valid_token_resets_password_and_clears_temporary_password_flag(): void
    {
        Notification::fake();
        $user = User::factory()->create(['must_change_password' => true]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPasswordNotification::class)->first();
        $password = 'Nouveau!Mot2Passe';

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertFalse($user->must_change_password);
    }
}
