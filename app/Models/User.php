<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $email
 * @property string $password_hash
 * @property string|null $name
 * @property string $role
 * @property \Carbon\CarbonInterface|null $email_verified_at
 * @property \Carbon\CarbonInterface|null $password_changed_at
 * @property \Carbon\CarbonInterface $created_at
 * @property \Carbon\CarbonInterface $updated_at
 */
final class User extends Model
{
    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'id',
        'email',
        'password_hash',
        'name',
        'role',
        'email_verified_at',
        'password_changed_at',
    ];

    /** @var list<string> */
    protected $hidden = ['password_hash'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return HasMany<RefreshToken, $this> */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }
}
