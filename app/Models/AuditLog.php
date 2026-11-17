<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string|null $actor_user_id
 * @property string $action
 * @property string|null $entity_type
 * @property string|null $entity_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed> $metadata
 * @property \Carbon\CarbonInterface $created_at
 */
final class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'id',
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'ip',
        'user_agent',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
