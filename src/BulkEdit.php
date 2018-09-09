<?php
/**
 * Bulk Edit plugin for Craft CMS 3.x
 *
 * Bulk edit entries
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\bulkedit;

use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use venveo\bulkedit\elements\actions\BulkEditElementAction;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 *
 * @property \venveo\bulkedit\services\BulkEdit bulkEdit
 */
class BulkEdit extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * BulkEdit::$plugin
     *
     * @var BulkEdit
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register CP routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        // Register element action to assets for clearing transforms
        Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions[] = BulkEditElementAction::class;
            }
        );
    }

    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $rules = [
        ];

        $event->rules = array_merge($event->rules, $rules);
    }

    /**
     * Performs actions before the plugin’s settings are saved.
     *
     * @return bool Whether the plugin’s settings should be saved.
     */
    public function beforeSaveSettings(): bool
    {
        // TODO: Implement beforeSaveSettings() method.
    }

    /**
     * Performs actions after the plugin’s settings are saved.
     */
    public function afterSaveSettings()
    {
        // TODO: Implement afterSaveSettings() method.
    }
}
