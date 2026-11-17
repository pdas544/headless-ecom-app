<?php

declare(strict_types=1);

namespace Tests\Support\Factories;

use App\Core\Uuid;
use Faker\Generator;
use Illuminate\Database\ConnectionInterface;

final class UserFactory
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
    public function create(array $overrides = []): array
    {
        $row = array_merge([
            'id' => Uuid::v7(),
            'email' => strtolower($this->faker->unique()->safeEmail()),
            'password_hash' => password_hash('Password123!', PASSWORD_ARGON2ID, [
                'memory_cost' => 8192,
                'time_cost' => 1,
                'threads' => 1,
            ]),
            'name' => $this->faker->name(),
            'role' => 'customer',
            'email_verified_at' => null,
            'password_changed_at' => null,
        ], $overrides);

        if (isset($row['email']) && is_string($row['email'])) {
            $row['email'] = strtolower($row['email']);
        }

        $this->database->table('users')->insert($row);

        return $row;
    }
}
