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
use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use venveo\bulkedit\elements\actions\BulkEditElementAction;
use venveo\bulkedit\services\BulkEdit;
use yii\base\Event;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 *
 * @property BulkEdit bulkEdit
 */
class Plugin extends BasePlugin
{

    /**
     * @var Plugin
     */
    public static $plugin;

    public $schemaVersion = '1.0.2';

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS,
            function (RegisterElementActionsEvent $event) {
                $event->actions[] = BulkEditElementAction::class;
            }
        );

        Event::on(Category::class, Element::EVENT_REGISTER_ACTIONS,
            function (RegisterElementActionsEvent $event) {
                $event->actions[] = BulkEditElementAction::class;
            }
        );
    }
}
