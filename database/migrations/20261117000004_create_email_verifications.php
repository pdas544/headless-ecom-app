<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmailVerifications extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE email_verifications (
                id uuid NOT NULL PRIMARY KEY,
                user_id uuid NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                token_hash char(64) NOT NULL,
                expires_at timestamptz NOT NULL,
                used_at timestamptz NULL,
                created_at timestamptz NOT NULL DEFAULT now()
            );

            CREATE UNIQUE INDEX email_verifications_token_hash_unique ON email_verifications (token_hash);
            CREATE INDEX email_verifications_user_id_idx ON email_verifications (user_id);
            CREATE INDEX email_verifications_expires_at_idx ON email_verifications (expires_at);
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS email_verifications CASCADE');
    }
}
