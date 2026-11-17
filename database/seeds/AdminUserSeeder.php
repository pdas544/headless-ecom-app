<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Uuid;
use Phinx\Seed\AbstractSeed;

/**
 * Creates (or refreshes) a development admin account. Refuses to run in
 * production and is idempotent: re-running updates the password/role of the
 * account with the same email.
 */
final class AdminUserSeeder extends AbstractSeed
{
    private const MIN_PASSWORD_LENGTH = 12;

    public function run(): void
    {
        $root = dirname(__DIR__, 2);
        $config = Config::load($root);

        if ($config->get('APP_ENV') === 'production') {
            throw new RuntimeException('Refusing to seed the development admin user in production.');
        }

        $email = strtolower(trim((string) $config->getOptional('ADMIN_EMAIL')));
        $password = $config->getOptional('ADMIN_PASSWORD');

        if ($email === '' || $password === null) {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be set to seed the admin user.',
            );
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException(
                'ADMIN_PASSWORD must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.',
            );
        }

        /** @var Closure(Config): array<string, mixed> $authConfigLoader */
        $authConfigLoader = require $root . '/config/auth.php';
        $auth = $authConfigLoader($config);
        $passwordConfig = $auth['password'] ?? null;

        if (!is_array($passwordConfig) || !isset($passwordConfig['options']) || !is_array($passwordConfig['options'])) {
            throw new RuntimeException('Password hashing options are missing from config/auth.php');
        }

        /** @var array{memory_cost: int, time_cost: int, threads: int} $hashOptions */
        $hashOptions = $passwordConfig['options'];
        $hash = password_hash($password, PASSWORD_ARGON2ID, $hashOptions);

        $this->execute(
            <<<'SQL'
            INSERT INTO users (id, email, password_hash, name, role, email_verified_at, created_at, updated_at)
            VALUES (:id, :email, :password_hash, :name, 'admin', now(), now(), now())
            ON CONFLICT ((lower(email))) DO UPDATE
            SET password_hash = EXCLUDED.password_hash,
                role = 'admin',
                email_verified_at = COALESCE(users.email_verified_at, now()),
                updated_at = now()
            SQL,
            [
                'id' => Uuid::v7(),
                'email' => $email,
                'password_hash' => $hash,
                'name' => 'Administrator',
            ],
        );
    }
}
