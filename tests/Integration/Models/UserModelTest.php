<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\RefreshToken;
use App\Models\User;
use App\Repositories\UserRepository;
use Tests\Integration\DatabaseTestCase;

final class UserModelTest extends DatabaseTestCase
{
    public function testHiddenPasswordAndDatetimeCasts(): void
    {
        $row = $this->users()->create([
            'email' => 'Casts@Example.test',
            'role' => 'admin',
            'email_verified_at' => '2026-01-02 03:04:05+00',
        ]);

        $user = User::query()->findOrFail($row['id']);

        self::assertSame('casts@example.test', $user->email);
        self::assertSame('admin', $user->role);
        self::assertInstanceOf(\DateTimeInterface::class, $user->email_verified_at);
        self::assertInstanceOf(\DateTimeInterface::class, $user->created_at);

        $serialized = $user->toArray();
        self::assertArrayNotHasKey('password_hash', $serialized);
        self::assertArrayHasKey('email', $serialized);
    }

    public function testRefreshTokensRelation(): void
    {
        $row = $this->users()->create();
        $this->refreshTokens()->create($row['id']);
        $this->refreshTokens()->create($row['id']);

        $user = User::query()->findOrFail($row['id']);

        self::assertCount(2, $user->refreshTokens);
        self::assertInstanceOf(RefreshToken::class, $user->refreshTokens->first());
        self::assertInstanceOf(User::class, $user->refreshTokens->first()?->user);
        self::assertSame($row['id'], $user->refreshTokens->first()?->user->id);
    }

    public function testRepositoryNormalizesEmailOnWriteAndLookup(): void
    {
        $repository = new UserRepository();
        $created = $repository->create('  Mixed.Case@Example.TEST ', 'hash', 'Mixed Case');

        self::assertSame('mixed.case@example.test', $created->email);
        self::assertTrue($repository->existsByEmail('MIXED.CASE@example.test'));
        self::assertSame($created->id, $repository->findByEmail('mixed.case@EXAMPLE.test')?->id);
        self::assertSame($created->id, $repository->findById($created->id)?->id);
    }

    public function testRepositoryUpdatesPasswordAndVerification(): void
    {
        $repository = new UserRepository();
        $user = $repository->create('flow@example.test', password_hash('old-password', PASSWORD_ARGON2ID));

        self::assertNull($user->email_verified_at);
        self::assertNull($user->password_changed_at);

        $repository->markEmailVerified($user);
        $repository->updatePassword($user, password_hash('new-password', PASSWORD_ARGON2ID));

        $fresh = User::query()->findOrFail($user->id);

        self::assertNotNull($fresh->email_verified_at);
        self::assertNotNull($fresh->password_changed_at);
        self::assertTrue(password_verify('new-password', (string) $fresh->password_hash));
        self::assertFalse(password_verify('old-password', (string) $fresh->password_hash));
    }
}
