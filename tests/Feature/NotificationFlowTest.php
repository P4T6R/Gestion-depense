<?php

namespace Tests\Feature;

use App\Models\Chantier;
use App\Models\User;
use App\Notifications\ChantierAffecteNotification;
use App\Notifications\DepenseSoumiseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_an_expense_notifies_active_reviewers_by_mail_and_in_app(): void
    {
        Notification::fake();

        $chef = User::factory()->chefChantier()->create();
        $admin = User::factory()->admin()->create();
        $directeur = User::factory()->create(['role' => 'directeur']);
        $comptable = User::factory()->comptable()->create();
        $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);
        $otherChef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);

        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', [
            'chantier_id' => $chantier->id,
            'numero_facture' => 'NOTIF-001',
            'lignes' => [
                ['designation' => 'Ciment', 'quantite' => 2, 'prix_unitaire' => 1500],
            ],
        ])->assertCreated();

        Notification::assertSentTo([$admin, $directeur, $comptable], DepenseSoumiseNotification::class, fn ($notification, $channels) => $channels === ['database']);
        Notification::assertSentTo([$admin, $directeur, $comptable], DepenseSoumiseNotification::class, fn ($notification, $channels) => $channels === ['mail']);
        Notification::assertNotSentTo([$inactiveAdmin, $otherChef, $chef], DepenseSoumiseNotification::class);
    }

    public function test_assigning_a_chantier_notifies_the_chef_only_once(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/chantiers/'.$chantier->id.'/affecter-chef', ['user_id' => $chef->id])
            ->assertOk();
        $this->postJson('/api/chantiers/'.$chantier->id.'/affecter-chef', ['user_id' => $chef->id])
            ->assertOk();

        Notification::assertSentTo($chef, ChantierAffecteNotification::class, fn ($notification, $channels) => $channels === ['database']);
        Notification::assertSentTo($chef, ChantierAffecteNotification::class, fn ($notification, $channels) => $channels === ['mail']);
        $this->assertCount(1, Notification::sent($chef, ChantierAffecteNotification::class, fn ($notification, $channels) => $channels === ['database']));
        $this->assertCount(1, Notification::sent($chef, ChantierAffecteNotification::class, fn ($notification, $channels) => $channels === ['mail']));
    }

    public function test_the_bell_lists_and_marks_only_the_authenticated_users_notifications_as_read(): void
    {
        $user = User::factory()->create(['role' => 'directeur']);
        $otherUser = User::factory()->comptable()->create();
        $userNotificationId = fake()->uuid();
        $otherNotificationId = fake()->uuid();

        DB::table('notifications')->insert([
            [
                'id' => $userNotificationId,
                'type' => DepenseSoumiseNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Nouvelle dÃ©pense soumise',
                    'message' => 'Une facture attend votre attention.',
                    'url' => '/depenses/1',
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $otherNotificationId,
                'type' => DepenseSoumiseNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $otherUser->id,
                'data' => json_encode([
                    'title' => 'Notification privÃ©e',
                    'message' => 'Cette alerte appartient Ã  un autre compte.',
                    'url' => '/depenses/2',
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $userNotificationId)
            ->assertJsonMissing(['id' => $otherNotificationId]);

        $this->postJson('/notifications/'.$otherNotificationId.'/read')->assertNotFound();
        $this->postJson('/notifications/'.$userNotificationId.'/read')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('url', '/depenses/1');

        $this->assertNotNull(DB::table('notifications')->where('id', $userNotificationId)->value('read_at'));
        $this->assertNull(DB::table('notifications')->where('id', $otherNotificationId)->value('read_at'));
    }

    public function test_marking_all_notifications_as_read_does_not_touch_another_users_notifications(): void
    {
        $user = User::factory()->admin()->create();
        $otherUser = User::factory()->comptable()->create();

        foreach ([$user, $user, $otherUser] as $index => $recipient) {
            DB::table('notifications')->insert([
                'id' => fake()->uuid(),
                'type' => DepenseSoumiseNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $recipient->id,
                'data' => json_encode(['title' => 'Alerte '.($index + 1), 'message' => 'Message', 'url' => '/']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($user)->postJson('/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $otherUser->unreadNotifications()->count());
    }
}
