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
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /*
     * @inheritdoc
     */

    /**
     * Creates all necessary tables for this plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%bulkedit_editcontext}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%bulkedit_editcontext}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),

                    'siteId' => $this->integer()->notNull(),
                    'ownerId' => $this->integer()->notNull(),
                    'elementIds' => $this->string(1024)->notNull(),
                    'fieldIds' => $this->string(1024)->notNull(),
                    'elementType' => $this->string()->notNull(),
                ]
            );
            $this->createTable(
                '{{%bulkedit_history}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'status' => $this->string()->notNull()->defaultValue('pending'),
                    'uid' => $this->uid(),

                    'contextId' => $this->integer()->notNull(),
                    'elementId' => $this->integer()->notNull(),
                    'fieldId' => $this->integer()->notNull(),
                    'siteId' => $this->integer()->notNull(),
                    'originalValue' => $this->text(),
                    'newValue' => $this->text(),
                    'strategy' => $this->string(16)->notNull()->defaultValue('replace')
                ]
            );
        }

        return $tablesCreated;
    }

    protected function createIndexes()
    {
        $this->createIndex(null, '{{%bulkedit_editcontext}}', ['ownerId'], false);
        $this->createIndex(null, '{{%bulkedit_history}}', ['contextId'], false);
        $this->createIndex(null, '{{%bulkedit_history}}', ['elementId'], false);
        $this->createIndex(null, '{{%bulkedit_history}}', ['fieldId'], false);
        $this->createIndex(null, '{{%bulkedit_history}}', ['status'], false);
        $this->createIndex(null, '{{%bulkedit_history}}', ['contextId', 'elementId', 'fieldId', 'siteId'], true);
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%bulkedit_editcontext}}', ['ownerId'], '{{%users}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%bulkedit_editcontext}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');

        $this->addForeignKey(null, '{{%bulkedit_history}}', ['contextId'], '{{%bulkedit_editcontext}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%bulkedit_history}}', ['elementId'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%bulkedit_history}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%bulkedit_history}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', null);
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * Remove all tables created by this plugin
     *
     * @return bool
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%bulkedit_history}}');
        $this->dropTableIfExists('{{%bulkedit_editcontext}}');
    }
}
