<?php

namespace venveo\bulkedit\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220620_172745_refactor_context_storage migration.
 */
class m220620_172745_refactor_context_storage extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists('{{%bulkedit_history}}');
        $this->dropTableIfExists('{{%bulkedit_editcontext}}');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220620_172745_refactor_context_storage cannot be reverted.\n";
        return false;
    }
}
