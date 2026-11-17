<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $family_id
 * @property string $token_hash
 * @property \Carbon\CarbonInterface $expires_at
 * @property \Carbon\CarbonInterface|null $revoked_at
 * @property string|null $replaced_by_id
 * @property string|null $user_agent
 * @property string|null $ip
 * @property \Carbon\CarbonInterface $created_at
 */
final class RefreshToken extends Model
{
    protected $table = 'refresh_tokens';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'id',
        'user_id',
        'family_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'replaced_by_id',
        'user_agent',
        'ip',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<self, $this> */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }
}
