<?php

namespace venveo\bulkedit\elements\processors;

use craft\elements\Category;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\records\CategoryGroup;
use craft\records\FieldLayout;
use craft\services\Users;
use venveo\bulkedit\base\AbstractElementTypeProcessor;

class CategoryProcessor extends AbstractElementTypeProcessor
{

    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return array
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

        $layouts = FieldLayout::find()->where(['in', 'id', $layoutIds])->all();

        return $layouts;
    }

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string
    {
        return get_class(new Category);
    }
}