<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use App\Models\EmailVerification;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class EmailVerificationRepository
{
    public function create(string $userId, string $tokenHash, DateTimeInterface $expiresAt): EmailVerification
    {
        $verification = new EmailVerification();
        $verification->fill([
            'id' => Uuid::v7(),
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
        $verification->save();

        return $verification;
    }

    public function findUsableByHash(string $tokenHash): ?EmailVerification
    {
        $verification = EmailVerification::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', CarbonImmutable::now('UTC'))
            ->first();

        return $verification instanceof EmailVerification ? $verification : null;
    }

    public function consume(EmailVerification $verification): void
    {
        if ($verification->used_at !== null) {
            return;
        }

        $verification->used_at = CarbonImmutable::now('UTC');
        $verification->save();
    }

    public function invalidateForUser(string $userId): int
    {
        return EmailVerification::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => CarbonImmutable::now('UTC')]);
    }
}
