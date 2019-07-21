<?php

namespace venveo\bulkedit\migrations;

use craft\db\Migration;

/**
 * m181213_193509_move_strategy_field migration.
 */
class m181213_193509_move_strategy_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn('{{%bulkedit_editcontext}}', 'strategy');
        $this->addColumn('{{%bulkedit_history}}', 'strategy', $this->string(16)->notNull()->defaultValue('replace'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%bulkedit_history}}', 'strategy');
        $this->addColumn('{{%bulkedit_editcontext}}', 'strategy', $this->string(16)->notNull()->defaultValue('replace'));
    }
}
