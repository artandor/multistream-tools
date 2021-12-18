<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210917180722 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE platform ADD enabled BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('UPDATE platform SET enabled = true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE platform DROP enabled');
    }
}
