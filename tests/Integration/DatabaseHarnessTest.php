<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Verifies that migrations reached the test database and that the per-test
 * transaction wrapper actually isolates fixtures.
 */
final class DatabaseHarnessTest extends DatabaseTestCase
{
    public function testCoreTablesExist(): void
    {
        $schema = $this->db()->getSchemaBuilder();

        foreach (
            [
            'users',
            'refresh_tokens',
            'password_resets',
            'email_verifications',
            'audit_logs',
            'phinx_migrations',
            ] as $table
        ) {
            self::assertTrue($schema->hasTable($table), "Missing table: {$table}");
        }
    }

    public function testPreviousTestWritesAreRolledBack(): void
    {
        self::assertSame(0, (int) $this->db()->table('users')->count());
    }

    public function testUserAndRefreshTokenFactories(): void
    {
        $user = $this->users()->create([
            'email' => 'Factory.User@Example.test',
            'role' => 'admin',
        ]);
        $token = $this->refreshTokens()->create($user['id']);

        self::assertSame('factory.user@example.test', $user['email']);
        self::assertSame(1, (int) $this->db()->table('users')->count());

        $stored = $this->db()->table('users')->where('id', $user['id'])->first();
        self::assertNotNull($stored);
        self::assertSame('admin', $stored->role);
        self::assertSame($user['email'], strtolower((string) $stored->email));

        $joined = $this->db()->table('refresh_tokens')
            ->join('users', 'users.id', '=', 'refresh_tokens.user_id')
            ->where('refresh_tokens.id', $token['id'])
            ->first(['refresh_tokens.token_hash', 'users.email']);
        self::assertNotNull($joined);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $joined->token_hash);

        $this->db()->table('users')->where('id', $user['id'])->delete();

        self::assertSame(
            0,
            (int) $this->db()->table('refresh_tokens')->where('user_id', $user['id'])->count(),
        );
    }
}
