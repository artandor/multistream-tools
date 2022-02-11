<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20220207210827 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE moderators (admin_id INT NOT NULL, moderator_id INT NOT NULL, PRIMARY KEY(admin_id, moderator_id))');
        $this->addSql('CREATE INDEX IDX_580D16D3642B8210 ON moderators (admin_id)');
        $this->addSql('CREATE INDEX IDX_580D16D3D0AFA354 ON moderators (moderator_id)');
        $this->addSql('ALTER TABLE moderators ADD CONSTRAINT FK_580D16D3642B8210 FOREIGN KEY (admin_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE moderators ADD CONSTRAINT FK_580D16D3D0AFA354 FOREIGN KEY (moderator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE moderators');
    }
}
