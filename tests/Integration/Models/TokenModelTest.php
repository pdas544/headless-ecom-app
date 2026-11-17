<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\AuditLog;
use App\Models\PasswordReset;
use App\Models\RefreshToken;
use App\Core\Uuid;
use App\Repositories\AuditLogRepository;
use App\Repositories\EmailVerificationRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\RefreshTokenRepository;
use Carbon\CarbonImmutable;
use Tests\Integration\DatabaseTestCase;

final class TokenModelTest extends DatabaseTestCase
{
    public function testRefreshTokenModelRelationshipAndHiddenHash(): void
    {
        $userRow = $this->users()->create();
        $tokenRow = $this->refreshTokens()->create($userRow['id']);

        $token = RefreshToken::query()->findOrFail($tokenRow['id']);

        self::assertInstanceOf(\DateTimeInterface::class, $token->expires_at);
        self::assertSame($userRow['id'], $token->user->id);
        self::assertArrayNotHasKey('token_hash', $token->toArray());
        self::assertNull($token->revoked_at);
    }

    public function testRepositoryRevokesFamilyAndMarksReplacement(): void
    {
        $repository = new RefreshTokenRepository();
        $userRow = $this->users()->create();
        $expiresAt = CarbonImmutable::now('UTC')->addDay();
        $family = Uuid::v7();

        $first = $repository->create($userRow['id'], $family, hash('sha256', 'one'), $expiresAt);
        $second = $repository->create($userRow['id'], $family, hash('sha256', 'two'), $expiresAt);
        $other = $repository->create($userRow['id'], Uuid::v7(), hash('sha256', 'three'), $expiresAt);

        self::assertSame($first->id, $repository->findByHash(hash('sha256', 'one'))?->id);

        $repository->markReplaced($first, $second);
        self::assertNotNull($first->revoked_at);
        self::assertSame($second->id, $first->replaced_by_id);

        self::assertSame(1, $repository->revokeFamily($family));
        self::assertNotNull($second->fresh()?->revoked_at);
        self::assertNull($other->fresh()?->revoked_at);

        self::assertSame(1, $repository->revokeAllForUser($userRow['id']));
    }

    public function testPasswordResetAndEmailVerificationRepositories(): void
    {
        $userRow = $this->users()->create();
        $resets = new PasswordResetRepository();
        $verifications = new EmailVerificationRepository();
        $expiresAt = CarbonImmutable::now('UTC')->addHour();

        $reset = $resets->create($userRow['id'], hash('sha256', 'reset'), $expiresAt);
        self::assertInstanceOf(PasswordReset::class, $reset);
        self::assertSame($reset->id, $resets->findUsableByHash(hash('sha256', 'reset'))?->id);

        $resets->consume($reset);
        self::assertNull($resets->findUsableByHash(hash('sha256', 'reset')));
        self::assertNotNull($reset->fresh()?->used_at);

        $verification = $verifications->create($userRow['id'], hash('sha256', 'verify'), $expiresAt);
        self::assertSame($verification->id, $verifications->findUsableByHash(hash('sha256', 'verify'))?->id);
        $verifications->consume($verification);
        self::assertNull($verifications->findUsableByHash(hash('sha256', 'verify')));

        $resets->create($userRow['id'], hash('sha256', 'expired'), CarbonImmutable::now('UTC')->subMinute());
        self::assertNull($resets->findUsableByHash(hash('sha256', 'expired')));
    }

    public function testAuditLogMetadataRoundTripsAsArray(): void
    {
        $userRow = $this->users()->create();
        $repository = new AuditLogRepository();

        $log = $repository->record(
            $userRow['id'],
            'auth.login.failed',
            'user',
            $userRow['id'],
            '127.0.0.1',
            'PHPUnit',
            ['reason' => 'invalid_credentials', 'attempt' => 1],
        );

        $fresh = AuditLog::query()->findOrFail($log->id);

        self::assertInstanceOf(\DateTimeInterface::class, $fresh->created_at);
        self::assertIsArray($fresh->metadata);
        self::assertSame('invalid_credentials', $fresh->metadata['reason']);
        self::assertSame(1, $fresh->metadata['attempt']);
        self::assertSame($userRow['id'], $fresh->actor?->id);
    }
}
