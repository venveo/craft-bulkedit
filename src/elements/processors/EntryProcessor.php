<?php

namespace venveo\bulkedit\elements\processors;

use craft\elements\Entry;
use craft\records\FieldLayout;
use venveo\bulkedit\base\AbstractElementTypeProcessor;

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
}