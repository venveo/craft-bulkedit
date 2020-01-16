<?php

namespace venveo\bulkedit\migrations;

use craft\db\Migration;

/**
 * m190721_201115_add_element_type migration.
 */
class m190721_201115_add_element_type extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%bulkedit_editcontext}}', 'elementType', $this->string()->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%bulkedit_editcontext}}', 'elementType');
    }
}
