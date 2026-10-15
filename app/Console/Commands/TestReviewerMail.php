<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationMail;
use App\Models\User;
use App\Notifications\MailConfigurationTestNotification;
use Illuminate\Console\Command;

class TestReviewerMail extends Command
{
    protected $signature = 'mail:test-reviewers {--send : Placer réellement les messages dans la file mail}';

    protected $description = 'Vérifie les destinataires administrateur, directeur et comptable';

    public function handle(): int
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['administrateur', 'directeur', 'comptable'])
            ->orderBy('role')
            ->get();

        if ($recipients->isEmpty()) {
            $this->error('Aucun destinataire actif trouvé.');

            return self::FAILURE;
        }

        $this->table(['Rôle', 'Nom', 'Adresse'], $recipients->map(fn (User $user) => [
            $user->role,
            $user->name,
            $this->mask($user->email),
        ]));

        if (! $this->option('send')) {
            $this->warn('Simulation seulement. Ajoutez --send après vérification des adresses et du SMTP.');

            return self::SUCCESS;
        }

        if (config('mail.default') !== 'smtp') {
            $this->error('Envoi refusé : MAIL_MAILER doit être smtp pour ce test réel.');

            return self::FAILURE;
        }

        foreach ($recipients as $recipient) {
            SendNotificationMail::dispatch($recipient, new MailConfigurationTestNotification());
        }

        $this->info($recipients->count().' courriel(s) placé(s) dans la file mail.');

        return self::SUCCESS;
    }

    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 2).str_repeat('*', max(2, mb_strlen($local) - 2)).'@'.$domain;
    }
}
