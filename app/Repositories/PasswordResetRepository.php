<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use App\Models\PasswordReset;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class PasswordResetRepository
{
    public function create(string $userId, string $tokenHash, DateTimeInterface $expiresAt): PasswordReset
    {
        $reset = new PasswordReset();
        $reset->fill([
            'id' => Uuid::v7(),
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
        $reset->save();

        return $reset;
    }

    public function findUsableByHash(string $tokenHash): ?PasswordReset
    {
        $reset = PasswordReset::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', CarbonImmutable::now('UTC'))
            ->first();

        return $reset instanceof PasswordReset ? $reset : null;
    }

    public function consume(PasswordReset $reset): void
    {
        if ($reset->used_at !== null) {
            return;
        }

        $reset->used_at = CarbonImmutable::now('UTC');
        $reset->save();
    }

    public function invalidateForUser(string $userId): int
    {
        return PasswordReset::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => CarbonImmutable::now('UTC')]);
    }
}
