<?php
/**
 * Bulk Edit plugin for Craft CMS 3.x
 *
 * Bulk edit entries
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2018 Venveo
 */

namespace venveo\bulkedit\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\fields\BaseRelationField;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\Date;
use craft\fields\Email;
use craft\fields\Lightswitch;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Table;
use craft\fields\Url;
use craft\records\FieldLayout;
use venveo\bulkedit\records\EditContext;
use venveo\bulkedit\records\History;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class BulkEdit extends Component
{
    /**
     * Get all distinct field layouts from a set of elements
     *
     * @param $elementIds
     */
    public function getFieldLayoutsForElementIds($elementIds)
    {
        $layouts = FieldLayout::find()
            ->select('fieldlayouts.*')
            ->distinct(true)
            ->limit(null)
            ->from('{{%fieldlayouts}} fieldlayouts')
            ->leftJoin('{{%elements}} elements', 'elements.fieldLayoutId = fieldlayouts.id')
            ->where(['IN', 'elements.id', $elementIds])
            ->all();

        $layoutsModels = [];
        /** @var FieldLayout $layout */
        foreach($layouts as $layout) {
            $layoutsModels[$layout->id] = ['fields' => \Craft::$app->fields->getFieldsByLayoutId($layout->id)];
        }
        return $layoutsModels;
    }

    public function getBulkEditContextFromId($id)
    {
        return EditContext::findOne($id);
    }

    /**
     * @param EditContext $context
     * @return array
     */
    public function getPendingElementIdsFromContext(EditContext $context): array
    {
        $items = array_keys(History::find()
            ->limit(null)
            ->where(['=', 'contextId', $context->id])
            ->andWhere(['=', 'status', 'pending'])->indexBy('elementId')->all());

        return $items;
    }

    /**
     * @param EditContext $context
     * @return \yii\db\ActiveQueryInterface
     */
    public function getPendingHistoryFromContext(EditContext $context): \yii\db\ActiveQueryInterface
    {
        return $context->getHistoryItems()->where(['=', 'status', 'pending']);
    }

    /**
     * @param EditContext $context
     * @param $elementId
     * @return \yii\db\ActiveQueryInterface
     */
    public function getPendingHistoryForElement(EditContext $context, $elementId): \yii\db\ActiveQueryInterface
    {
        $items = $this->getPendingHistoryFromContext($context);
        $items->where(['=', 'elementId', $elementId]);
        return $items;
    }

    /**
     * Takes an array of history changes for a particular element and saves it to that element.
     *
     * @param $historyItems
     * @param \craft\base\Element $element
     * @return \craft\base\Element
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function processHistoryItemsForElement($historyItems, \craft\base\Element $element)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($historyItems as $historyItem) {
                $historyItem->originalValue = \GuzzleHttp\json_encode($element->getFieldValue($historyItem->field->handle));
                $historyItem->status = 'completed';
                $element->setFieldValue($historyItem->field->handle, \GuzzleHttp\json_decode($historyItem->newValue));
                $historyItem->save();
                Craft::info('Saved history item', __METHOD__);
            }
            $element->setScenario(Element::SCENARIO_ESSENTIALS);
            \Craft::$app->elements->saveElement($element, false);

            switch (get_class($element)) {
                case Entry::class:
                    // Save a revision
                    \Craft::$app->entryRevisions->saveVersion($element);
                    break;
                default:
                    break;
            }

            Craft::info('Saved element', __METHOD__);
            $transaction->commit();
            return $element;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Craft::error('Transaction rolled back', __METHOD__);
            throw $e;
        }
    }

    public function isFieldSupported(FieldInterface $field): bool
    {
        $supportedFields = [
            PlainText::class,
            Number::class,
            BaseRelationField::class,
            Color::class,
            Checkboxes::class,
            Date::class,
            Table::class,
            RadioButtons::class,
            Lightswitch::class,
            Url::class,
            Email::class,
            MultiSelect::class
        ];

        foreach ($supportedFields as $fieldItem) {
            if ($field instanceof $fieldItem) {
                return true;
            }
        }
        return false;
    }
}
