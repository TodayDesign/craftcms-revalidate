<?php
namespace today\revalidate\migrations;

use craft\db\Migration;

class m240920_000000_create_deployment_status_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%revalidate_deployment_status}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'createdAt' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%revalidate_deployment_status}}');
    }
}
