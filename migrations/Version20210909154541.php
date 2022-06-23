<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Provider\BrimeProvider;
use App\Provider\GoogleProvider;
use App\Provider\TwitchProvider;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210909154541 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE account_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE platform_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE account (id INT NOT NULL, linked_to_id INT NOT NULL, platform_id INT NOT NULL, email VARCHAR(255) NOT NULL, access_token TEXT NOT NULL, refresh_token TEXT DEFAULT NULL, external_id VARCHAR(255) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7D3656A48031A592 ON account (linked_to_id)');
        $this->addSql('CREATE INDEX IDX_7D3656A4FFE6496F ON account (platform_id)');
        $this->addSql('COMMENT ON COLUMN account.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN account.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE platform (id INT NOT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, provider VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A48031A592 FOREIGN KEY (linked_to_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4FFE6496F FOREIGN KEY (platform_id) REFERENCES platform (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO platform (id, name, provider, path, image) VALUES (1, 'Twitch', '".TwitchProvider::class."', 'connect_twitch_start', 'https://images-eu.ssl-images-amazon.com/images/I/21kRx-CJsUL.png')");
        $this->addSql("INSERT INTO platform (id, name, provider, path, image) VALUES (2, 'Brime', '".BrimeProvider::class."', 'connect_brime_start', 'https://f004.backblazeb2.com/file/brime-assets/brime_mst.png')");
        $this->addSql("INSERT INTO platform (id, name, provider, path, image) VALUES (3, 'Youtube', '".GoogleProvider::class."', 'connect_google_start', 'https://www.youtube.com/img/desktop/yt_1200.png')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE account DROP CONSTRAINT FK_7D3656A4FFE6496F');
        $this->addSql('ALTER TABLE account DROP CONSTRAINT FK_7D3656A48031A592');
        $this->addSql('DROP SEQUENCE account_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE platform_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE platform');
        $this->addSql('DROP TABLE "user"');
    }
}
