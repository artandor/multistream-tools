<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Provider\TrovoProvider;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20211013094039 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO platform (id, name, provider, path, image) VALUES (4, 'Trovo', '".TrovoProvider::class."', 'connect_trovo_start', 'https://seeklogo.com/images/T/trovo-stream-live-logo-C70BD4D4F8-seeklogo.com.png')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM platform WHERE name = 'Trovo'");
    }
}
