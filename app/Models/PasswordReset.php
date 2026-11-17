<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $token_hash
 * @property \Carbon\CarbonInterface $expires_at
 * @property \Carbon\CarbonInterface|null $used_at
 * @property \Carbon\CarbonInterface $created_at
 */
final class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = ['id', 'user_id', 'token_hash', 'expires_at', 'used_at'];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
