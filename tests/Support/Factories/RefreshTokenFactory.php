<?php

declare(strict_types=1);

namespace Tests\Support\Factories;

use App\Core\Uuid;
use Faker\Generator;
use Illuminate\Database\ConnectionInterface;

final class RefreshTokenFactory
{
    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly Generator $faker,
    ) {
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public function create(string $userId, array $overrides = []): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $row = array_merge([
            'id' => Uuid::v7(),
            'user_id' => $userId,
            'family_id' => Uuid::v7(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:sP', time() + 86400),
            'revoked_at' => null,
            'replaced_by_id' => null,
            'user_agent' => $this->faker->userAgent(),
            'ip' => '127.0.0.1',
        ], $overrides);

        $this->database->table('refresh_tokens')->insert($row);

        return $row;
    }
}
