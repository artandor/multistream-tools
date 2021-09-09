<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210909125612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add platform handling.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE platform_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE platform (id INT NOT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE platform_id_seq CASCADE');
        $this->addSql('DROP TABLE platform');
    }

    public function postUp(Schema $schema): void
    {
        $this->addSql("INSERT INTO platform (id, name, path, image) VALUES (1, 'App\Provider\TwitchProvider', 'connect_twitch_start', 'https://images-eu.ssl-images-amazon.com/images/I/21kRx-CJsUL.png')");
        $this->addSql("INSERT INTO platform (id, name, path, image) VALUES (2, 'App\Provider\BrimeProvider', 'connect_brime_start', 'https://pbs.twimg.com/profile_images/1377892219554775047/XgvkhoAI.jpg')");
        $this->addSql("INSERT INTO platform (id, name, path, image) VALUES (3, 'App\Provider\GoogleProvider', 'connect_google_start', 'https://www.youtube.com/img/desktop/yt_1200.png')");
    }
}
