<?php

namespace venveo\bulkedit\elements\processors;

use Craft;
use craft\elements\User;
use craft\records\FieldLayout;
use craft\services\Users;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\Plugin;

class UserProcessor extends AbstractElementTypeProcessor
{

    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return array
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        $projectConfig = Craft::$app->projectConfig;
        $fieldLayouts = $projectConfig->get(Users::CONFIG_USERLAYOUT_KEY);
        $fieldLayoutsUIDs = array_keys($fieldLayouts);
        $layouts = FieldLayout::find()
            ->where(['in', 'uid', $fieldLayoutsUIDs])
            ->all();
        return $layouts;
    }

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string
    {
        return User::class;
    }

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     * @param $elementIds
     * @param $user
     * @return bool
     */
    public static function hasPermission($elementIds, \craft\web\User $user): bool
    {
        return $user->checkPermission(Plugin::PERMISSION_BULKEDIT_USERS);
    }
}