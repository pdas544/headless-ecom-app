<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE users (
                id uuid NOT NULL PRIMARY KEY,
                email varchar(254) NOT NULL,
                password_hash varchar(255) NOT NULL,
                name varchar(100) NULL,
                role varchar(20) NOT NULL DEFAULT 'customer'
                    CONSTRAINT users_role_check CHECK (role IN ('admin', 'customer', 'seller_reserved')),
                email_verified_at timestamptz NULL,
                password_changed_at timestamptz NULL,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            );

            CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email));
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS users CASCADE');
    }
}
