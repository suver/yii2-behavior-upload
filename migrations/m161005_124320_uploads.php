<?php

use yii\db\Migration;

class m161005_124320_uploads extends Migration
{
    public function up()
    {
        $this->db->createCommand("CREATE TABLE `uploads` ( 
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT , 
            `attribute` VARCHAR(255) NOT NULL ,
            `model` VARCHAR(255) NOT NULL , 
            `parent_id` BIGINT(20) NOT NULL , 
            `mime_type` VARCHAR(255) NULL DEFAULT NULL , 
            `size` BIGINT(20) NULL DEFAULT NULL , 
            `original_name` VARCHAR(255) NULL DEFAULT NULL , 
            `params` TEXT NULL DEFAULT NULL ,
            `name` VARCHAR(255) NULL DEFAULT NULL , 
            `hash` VARCHAR(40) NULL DEFAULT NULL , 
            `extension` VARCHAR(10) NULL DEFAULT NULL , 
            `type` TINYINT(3) NULL DEFAULT NULL , 
            `created_at` TIMESTAMP NULL DEFAULT NULL , 
            `updated_at` TIMESTAMP NULL DEFAULT NULL , 
            PRIMARY KEY (`id`), 
            INDEX `linked` (`model`, `parent_id`, `attribute`),
            INDEX `hash` (`hash`),
            INDEX `name` (`name`)
        ) ENGINE = InnoDB;")->execute();
    }

    public function down()
    {
        echo "m161005_124320_uploads cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
