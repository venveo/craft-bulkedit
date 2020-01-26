<?php

namespace venveo\bulkedit\elements\processors;

use craft\elements\Entry;
use craft\records\FieldLayout;
use craft\web\User;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\Plugin;
use venveo\bulkedit\services\BulkEdit;

class EntryProcessor extends AbstractElementTypeProcessor
{

    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return array
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        $layouts = FieldLayout::find()
            ->select('fieldlayouts.*')
            ->distinct(true)
            ->limit(null)
            ->from('{{%fieldlayouts}} fieldlayouts')
            ->leftJoin('{{%elements}} elements', '[[elements.fieldLayoutId]] = [[fieldlayouts.id]]')
            ->where(['IN', '[[elements.id]]', $elementIds])
            ->all();

        return $layouts;
    }

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string
    {
        return get_class(new Entry);
    }

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     * @param $elementIds
     * @param $user
     * @return bool
     */
    public static function hasPermission($elementIds, User $user): bool
    {
        return $user->checkPermission(Plugin::PERMISSION_BULKEDIT_ENTRIES);
    }

    public static function getEditableAttributes(): array {
        return [
            [
                'name' => 'Title',
                'handle' => 'title',
                'strategies' => [
                    BulkEdit::STRATEGY_REPLACE
                ]
            ]
        ];
    }
}