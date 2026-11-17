<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Uuid;
use App\Models\AuditLog;

final class AuditLogRepository
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        ?string $actorUserId,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $ip = null,
        ?string $userAgent = null,
        array $metadata = [],
    ): AuditLog {
        $log = new AuditLog();
        $log->fill([
            'id' => Uuid::v7(),
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
        ]);
        $log->save();

        return $log;
    }
}
