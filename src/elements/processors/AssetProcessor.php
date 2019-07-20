<?php

namespace venveo\bulkedit\elements\processors;

use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\records\FieldLayout;
use craft\services\Users;
use venveo\bulkedit\base\AbstractElementTypeProcessor;

class AssetProcessor extends AbstractElementTypeProcessor
{

    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return array
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        $groups = \craft\records\Asset::find()
            ->select('volumeId')
            ->distinct(true)
            ->limit(null)
            ->where(['IN', '[[id]]', $elementIds])
            ->asArray()
            ->all();
        $groupIds = ArrayHelper::getColumn($groups, 'volumeId');

        $layouts = FieldLayout::find()->where(['in', 'id', $groupIds])->all();

        return $layouts;
    }

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string
    {
        return get_class(new Asset);
    }
}