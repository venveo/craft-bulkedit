<?php

namespace venveo\bulkedit\elements\processors;

use Craft;
use craft\base\Element;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use craft\records\CategoryGroup;
use craft\web\User;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\Plugin;

class CategoryProcessor extends AbstractElementTypeProcessor
{
    /**
     * Gets a unique list of field layouts from a list of element IDs
     *
     * @param $elementIds
     * @return FieldLayout[]
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        // Get the category groups (there *should* only be one)
        $groups = \craft\records\Category::find()
            ->select('groupId')
            ->distinct(true)
            ->limit(null)
            ->where(['IN', '[[id]]', $elementIds])
            ->asArray()
            ->all();
        $groupIds = ArrayHelper::getColumn($groups, 'groupId');

        $layouts = CategoryGroup::find()
            ->select('fieldLayoutId')
            ->where(['IN', '[[id]]', $groupIds])
            ->asArray()
            ->all();
        $layoutIds = ArrayHelper::getColumn($layouts, 'fieldLayoutId');
        
        return Craft::$app->fields->getLayoutsByIds($layoutIds);
    }

    /**
     * The fully qualified class name for the element this processor works on
     */
    public static function getType(): string
    {
        return Category::class;
    }

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     * @param $elementIds
     * @param $user
     */
    public static function hasPermission($elementIds, User $user): bool
    {
        return $user->checkPermission(Plugin::PERMISSION_BULKEDIT_CATEGORIES);
    }

    public static function getMockElement($elementIds = [], $params = []): Element
    {
        $elementPlaceholder = parent::getMockElement($elementIds, $params);
        $category = Craft::$app->categories->getCategoryById($elementIds[0], $params['siteId']);
        /** @var Category $elementPlaceholder */
        // Field availability is determined by volume ID
        $elementPlaceholder->groupId = $category->groupId;
        return $elementPlaceholder;
    }
}
