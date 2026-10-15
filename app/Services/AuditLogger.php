<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(
        string $event,
        string $description,
        ?User $actor = null,
        ?Model $auditable = null,
        ?User $targetUser = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'target_user_id' => $targetUser?->id,
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $this->withoutSecrets($oldValues),
            'new_values' => $this->withoutSecrets($newValues),
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
        ]);
    }

    private function withoutSecrets(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return collect($values)->except([
            'password', 'password_confirmation', 'current_password', 'token',
            'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        ])->all();
    }
}
