<?php

namespace venveo\bulkedit\elements\processors;

use Craft;
use craft\elements\User;
use craft\models\FieldLayout;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\Plugin;

class UserProcessor extends AbstractElementTypeProcessor
{
    /**
     * Gets a unique list of field layouts from a list of element IDs
     *
     * @param $elementIds
     * @return FieldLayout[]
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        return [Craft::$app->fields->getLayoutByType(User::class)];
    }

    /**
     * The fully qualified class name for the element this processor works on
     */
    public static function getType(): string
    {
        return User::class;
    }

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     *
     * @param $elementIds
     * @param $user
     */
    public static function hasPermission($elementIds, \craft\web\User $user): bool
    {
        return $user->checkPermission(Plugin::PERMISSION_BULKEDIT_USERS);
    }
}
