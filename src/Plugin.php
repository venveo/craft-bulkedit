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

use Craft;
use craft\base\Element;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
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

    const PERMISSION_BULKEDIT_ENTRIES = 'PERMISSION_BULKEDIT_ENTRIES';
    const PERMISSION_BULKEDIT_PRODUCTS = 'PERMISSION_BULKEDIT_PRODUCTS';
    const PERMISSION_BULKEDIT_ASSETS = 'PERMISSION_BULKEDIT_ASSETS';
    const PERMISSION_BULKEDIT_CATEGORIES = 'PERMISSION_BULKEDIT_CATEGORIES';
    const PERMISSION_BULKEDIT_USERS = 'PERMISSION_BULKEDIT_USERS';
    /**
     * @var Plugin
     */
    public static $plugin;
    public $schemaVersion = '1.1.0';

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents(['bulkEdit' => BulkEdit::class]);

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function (RegisterUserPermissionsEvent $event) {
            $permissions = [];
            $permissions[self::PERMISSION_BULKEDIT_ENTRIES] = [
                'label' => Craft::t('venveo-bulk-edit', 'Bulk Edit Entries')
            ];
            $permissions[self::PERMISSION_BULKEDIT_ASSETS] = [
                'label' => Craft::t('venveo-bulk-edit', 'Bulk Edit Assets')
            ];
            $permissions[self::PERMISSION_BULKEDIT_CATEGORIES] = [
                'label' => Craft::t('venveo-bulk-edit', 'Bulk Edit Categories')
            ];
            $permissions[self::PERMISSION_BULKEDIT_USERS] = [
                'label' => Craft::t('venveo-bulk-edit', 'Bulk Edit Users')
            ];

            if (Craft::$app->plugins->isPluginInstalled('commerce')) {
                $permissions[self::PERMISSION_BULKEDIT_PRODUCTS] = [
                    'label' => Craft::t('venveo-bulk-edit', 'Bulk Edit Products')
                ];
            }

            $event->permissions[Craft::t('venveo-bulk-edit', 'Bulk Edit')] = $permissions;
        });

        if (Craft::$app->request->isCpRequest) {
            if (Craft::$app->user->checkPermission(self::PERMISSION_BULKEDIT_ENTRIES)) {
                Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS,
                    function (RegisterElementActionsEvent $event) {
                        $event->actions[] = BulkEditElementAction::class;
                    }
                );
            }

            if (Craft::$app->user->checkPermission(self::PERMISSION_BULKEDIT_CATEGORIES)) {
                Event::on(Category::class, Element::EVENT_REGISTER_ACTIONS,
                    function (RegisterElementActionsEvent $event) {
                        $event->actions[] = BulkEditElementAction::class;
                    }
                );
            }

            if (Craft::$app->user->checkPermission(self::PERMISSION_BULKEDIT_ASSETS)) {
                Event::on(Asset::class, Element::EVENT_REGISTER_ACTIONS,
                    function (RegisterElementActionsEvent $event) {
                        $event->actions[] = BulkEditElementAction::class;
                    }
                );
            }

            if (Craft::$app->user->checkPermission(self::PERMISSION_BULKEDIT_USERS)) {
                Event::on(User::class, Element::EVENT_REGISTER_ACTIONS,
                    function (RegisterElementActionsEvent $event) {
                        $event->actions[] = BulkEditElementAction::class;
                    }
                );
            }

            if (Craft::$app->user->checkPermission(self::PERMISSION_BULKEDIT_PRODUCTS)) {
                if (Craft::$app->plugins->isPluginInstalled('commerce') && class_exists(Product::class)) {
                    Event::on(Product::class, Element::EVENT_REGISTER_ACTIONS,
                        function (RegisterElementActionsEvent $event) {
                            $event->actions[] = BulkEditElementAction::class;
                        }
                    );
                }
            }
        }
    }
}
