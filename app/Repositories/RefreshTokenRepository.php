<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use App\Models\RefreshToken;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class RefreshTokenRepository
{
    public function create(
        string $userId,
        string $familyId,
        string $tokenHash,
        DateTimeInterface $expiresAt,
        ?string $userAgent = null,
        ?string $ip = null,
    ): RefreshToken {
        $token = new RefreshToken();
        $token->fill([
            'id' => Uuid::v7(),
            'user_id' => $userId,
            'family_id' => $familyId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'user_agent' => $userAgent,
            'ip' => $ip,
        ]);
        $token->save();

        return $token;
    }

    public function findByHash(string $tokenHash): ?RefreshToken
    {
        $token = RefreshToken::query()->where('token_hash', $tokenHash)->first();

        return $token instanceof RefreshToken ? $token : null;
    }

    public function revoke(RefreshToken $token): void
    {
        if ($token->revoked_at !== null) {
            return;
        }

        $token->revoked_at = CarbonImmutable::now('UTC');
        $token->save();
    }

    public function markReplaced(RefreshToken $token, RefreshToken $replacement): void
    {
        $token->replaced_by_id = (string) $replacement->getKey();
        $token->revoked_at = CarbonImmutable::now('UTC');
        $token->save();
    }

    public function revokeFamily(string $familyId): int
    {
        return RefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => CarbonImmutable::now('UTC')]);
    }

    public function revokeAllForUser(string $userId): int
    {
        return RefreshToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => CarbonImmutable::now('UTC')]);
    }
}
