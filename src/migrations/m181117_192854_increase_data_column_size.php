<?php

namespace venveo\bulkedit\migrations;

use craft\db\Migration;

/**
 * m181117_192854_increase_data_column_size migration.
 */
class m181117_192854_increase_data_column_size extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Place migration code here...
        $this->alterColumn('{{%bulkedit_history}}', 'originalValue', $this->text());
        $this->alterColumn('{{%bulkedit_history}}', 'newValue', $this->text());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181117_192854_increase_data_column_size cannot be reverted.\n";
        return false;
    }
}
