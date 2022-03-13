<?php

namespace venveo\bulkedit\elements\processors;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\models\FieldLayout;
use craft\web\User;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\Plugin;
use venveo\bulkedit\services\BulkEdit;

class EntryProcessor extends AbstractElementTypeProcessor
{
    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return FieldLayout[]
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        $fieldLayoutIds = \craft\records\Entry::find()
            ->select('entrytypes.fieldLayoutId')
            ->distinct(true)
            ->limit(null)
            ->from('{{%entries}} entries')
            ->leftJoin('{{%entrytypes}} entrytypes', '[[entries.typeId]] = [[entrytypes.id]]')
            ->where(['IN', '[[entries.id]]', $elementIds])
            ->column();
        
        return Craft::$app->fields->getLayoutsByIds($fieldLayoutIds);
    }

    /**
     * The fully qualified class name for the element this processor works on
     */
    public static function getType(): string
    {
        return Entry::class;
    }

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     * @param $elementIds
     * @param $user
     */
    public static function hasPermission($elementIds, User $user): bool
    {
        return $user->checkPermission(Plugin::PERMISSION_BULKEDIT_ENTRIES);
    }

    /**
     * @return mixed[]
     */
    public static function getEditableAttributes(): array
    {
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
     * @throws \yii\base\InvalidConfigException
     */
    public static function getMockElement($elementIds = [], $params = []): Element
    {
        $elementPlaceholder = parent::getMockElement($elementIds, $params);
        $templateEntry = Craft::$app->entries->getEntryById($elementIds[0], $params['siteId']);
        $elementPlaceholder->typeId = $templateEntry->typeId;
        $elementPlaceholder->sectionId = $templateEntry->sectionId;
        return $elementPlaceholder;
    }
}
