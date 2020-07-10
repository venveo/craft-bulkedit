<?php

namespace venveo\bulkedit\elements\processors;

use Craft;
use craft\base\Element;
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
        $types = \craft\records\Entry::find()
            ->select('entrytypes.fieldLayoutId')
            ->distinct(true)
            ->limit(null)
            ->from('{{%entries}} entries')
            ->leftJoin('{{%entrytypes}} entrytypes', '[[entries.typeId]] = [[entrytypes.id]]')
            ->where(['IN', '[[entries.id]]', $elementIds])
            ->column();

        return FieldLayout::find()->where(['IN', '[[id]]', $types])->all();
    }

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string
    {
        return Entry::class;
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

    /**
     * @return array
     */
    public static function getEditableAttributes(): array {
//        return [
//            [
//                'name' => 'Title',
//                'handle' => 'title',
//                'strategies' => [
//                    BulkEdit::STRATEGY_REPLACE
//                ]
//            ]
//        ];
        return [];
    }

    /**
     * @param array $elementIds
     * @param array $params
     * @return Element
     * @throws \yii\base\InvalidConfigException
     */
    public static function getMockElement($elementIds = [], $params = []): Element
    {
        $templateEntry = Craft::$app->entries->getEntryById($elementIds[0], $params['siteId']);
        /** @var Entry $entry */
        $entry = Craft::createObject(self::getType(), $params);
        $entry->typeId = $templateEntry->typeId;
        $entry->sectionId = $templateEntry->sectionId;
        return $entry;
    }
}
