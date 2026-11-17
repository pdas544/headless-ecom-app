<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use App\Models\User;
use Carbon\CarbonImmutable;

final class UserRepository
{
    public function findById(string $id): ?User
    {
        $user = User::query()->find($id);

        return $user instanceof User ? $user : null;
    }

    public function findByEmail(string $email): ?User
    {
        $user = User::query()
            ->whereRaw('lower(email) = ?', [$this->normalizeEmail($email)])
            ->first();

        return $user instanceof User ? $user : null;
    }

    public function existsByEmail(string $email): bool
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [$this->normalizeEmail($email)])
            ->exists();
    }

    public function create(
        string $email,
        string $passwordHash,
        ?string $name = null,
        string $role = 'customer',
    ): User {
        $user = new User();
        $user->fill([
            'id' => Uuid::v7(),
            'email' => $this->normalizeEmail($email),
            'password_hash' => $passwordHash,
            'name' => $name,
            'role' => $role,
        ]);
        $user->save();

        return $user;
    }

    public function updatePassword(User $user, string $passwordHash): void
    {
        $user->password_hash = $passwordHash;
        $user->password_changed_at = CarbonImmutable::now('UTC');
        $user->save();
    }

    public function markEmailVerified(User $user): void
    {
        if ($user->email_verified_at !== null) {
            return;
        }

        $user->email_verified_at = CarbonImmutable::now('UTC');
        $user->save();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
