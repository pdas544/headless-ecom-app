<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuditLogs extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE audit_logs (
                id uuid NOT NULL PRIMARY KEY,
                actor_user_id uuid NULL REFERENCES users (id) ON DELETE SET NULL,
                action varchar(64) NOT NULL,
                entity_type varchar(64) NULL,
                entity_id varchar(64) NULL,
                ip inet NULL,
                user_agent varchar(512) NULL,
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb,
                created_at timestamptz NOT NULL DEFAULT now()
            );

            CREATE INDEX audit_logs_actor_user_id_created_at_idx ON audit_logs (actor_user_id, created_at);
            CREATE INDEX audit_logs_action_created_at_idx ON audit_logs (action, created_at);
            CREATE INDEX audit_logs_created_at_idx ON audit_logs (created_at);
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS audit_logs CASCADE');
    }
}
