<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePasswordResets extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE password_resets (
                id uuid NOT NULL PRIMARY KEY,
                user_id uuid NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                token_hash char(64) NOT NULL,
                expires_at timestamptz NOT NULL,
                used_at timestamptz NULL,
                created_at timestamptz NOT NULL DEFAULT now()
            );

            CREATE UNIQUE INDEX password_resets_token_hash_unique ON password_resets (token_hash);
            CREATE INDEX password_resets_user_id_idx ON password_resets (user_id);
            CREATE INDEX password_resets_expires_at_idx ON password_resets (expires_at);
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS password_resets CASCADE');
    }
}
