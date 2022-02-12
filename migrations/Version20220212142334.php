<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20220212142334 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL PRIMARY KEY, sess_data BYTEA NOT NULL, sess_lifetime INTEGER NOT NULL, sess_time INTEGER NOT NULL);');
        $this->addSql('CREATE INDEX sessions_sess_lifetime_idx ON sessions (sess_lifetime);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sessions');
    }
}
