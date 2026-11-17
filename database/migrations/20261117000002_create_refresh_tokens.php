<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRefreshTokens extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE refresh_tokens (
                id uuid NOT NULL PRIMARY KEY,
                user_id uuid NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                family_id uuid NOT NULL,
                token_hash char(64) NOT NULL,
                expires_at timestamptz NOT NULL,
                revoked_at timestamptz NULL,
                replaced_by_id uuid NULL REFERENCES refresh_tokens (id) ON DELETE SET NULL,
                user_agent varchar(512) NULL,
                ip inet NULL,
                created_at timestamptz NOT NULL DEFAULT now()
            );

            CREATE UNIQUE INDEX refresh_tokens_token_hash_unique ON refresh_tokens (token_hash);
            CREATE INDEX refresh_tokens_user_id_idx ON refresh_tokens (user_id);
            CREATE INDEX refresh_tokens_family_id_idx ON refresh_tokens (family_id);
            CREATE INDEX refresh_tokens_expires_at_idx ON refresh_tokens (expires_at);
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS refresh_tokens CASCADE');
    }
}
