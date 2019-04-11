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
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use venveo\bulkedit\elements\actions\BulkEditElementAction;
use yii\base\Event;

/**
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
    public $schemaVersion = '1.0.2';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions[] = BulkEditElementAction::class;
            }
        );

        Event::on(Category::class, Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions[] = BulkEditElementAction::class;
            }
        );
    }
}
