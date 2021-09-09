<?php

declare(strict_types = 1);

namespace <namespace>;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class <className> extends AbstractMigration
{
    /**
    * @param \Doctrine\DBAL\Schema\Schema $schema
    *
    * @return void
    */
    public function up(Schema $schema): void
    {
    <up>
    }

    /**
    * @param \Doctrine\DBAL\Schema\Schema $schema
    *
    * @return void
    */
    public function down(Schema $schema): void
    {
    <down>
    }
}
