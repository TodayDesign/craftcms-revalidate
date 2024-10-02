<?php
namespace today\revalidate\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%revalidate_deployment_status}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'createdAt' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTable('{{%revalidate_deployment_status}}');

        return true;
    }
}
