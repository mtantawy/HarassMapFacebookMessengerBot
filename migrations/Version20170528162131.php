<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170528162131 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE `reports` ADD `datetime` DATETIME NULL;
            UPDATE `reports` SET `datetime`= TIMESTAMP(date, time);
        ");
        $this->addSql("ALTER TABLE `reports` DROP COLUMN `date`;");
        $this->addSql("ALTER TABLE `reports` DROP COLUMN `time`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE `reports` ADD `date` DATE NULL;
            ALTER TABLE `reports` ADD `time` TIME NULL;
        ");
        $this->addSql("
            UPDATE `reports` SET `date`= date(datetime);
            UPDATE `reports` SET `time`= time(datetime);
        ");
        $this->addSql("ALTER TABLE `reports` DROP COLUMN `datetime`;");
    }
}
