<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170423115802 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE `users` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `psid` VARCHAR(255) NOT NULL,
                `first_name` VARCHAR(255) NOT NULL,
                `last_name` VARCHAR(255) NOT NULL,
                `locale` VARCHAR(10) NOT NULL,
                `timezone` TINYINT NOT NULL,
                `gender` TINYINT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`psid`)
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE `users`");
    }
}
