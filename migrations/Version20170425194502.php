<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170425194502 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE `reports` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `step` VARCHAR(255) NOT NULL,
                `relation` VARCHAR(255) NULL,
                `details` TEXT NULL,
                `date` DATE NULL,
                `time` TIME NULL,
                `harassment_type` VARCHAR(255) NULL,
                `harassment_type_details` VARCHAR(255) NULL,
                `assistence_offered` TINYINT(1) NULL,
                `latitude` DECIMAL(9,7) NULL,
                `longitude` DECIMAL(10,7) NULL
            ) ENGINE = InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE `reports`");
    }
}
