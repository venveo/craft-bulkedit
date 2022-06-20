<?php

namespace venveo\bulkedit\migrations;

use Craft;
use craft\db\Migration;

/**
 * The Install Migration covers all install migrations
 * @since 1.0
 */
class Install extends Migration
{
    public $driver;

    /*
     * @inheritdoc
     */
    public function safeUp(): bool
    {
       $this->removeTables();
       return true;
    }



    public function safeDown(): bool
    {
        $this->removeTables();
        return true;
    }

    protected function removeTables(): void
    {
        $this->dropTableIfExists('{{%bulkedit_history}}');
        $this->dropTableIfExists('{{%bulkedit_editcontext}}');
    }
}
