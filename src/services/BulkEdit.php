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
use craft\base\Field;
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\events\RegisterComponentTypesEvent;
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
use craft\redactor\Field as RedactorField;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\elements\processors\AssetProcessor;
use venveo\bulkedit\elements\processors\CategoryProcessor;
use venveo\bulkedit\elements\processors\EntryProcessor;
use venveo\bulkedit\elements\processors\UserProcessor;
use venveo\bulkedit\models\FieldWrapper;
use venveo\bulkedit\records\EditContext;
use venveo\bulkedit\records\History;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 */
class BulkEdit extends Component
{
    public const STRATEGY_REPLACE = 'replace';
    public const STRATEGY_MERGE = 'merge';
    public const STRATEGY_SUBTRACT = 'subtract';

    public const EVENT_REGISTER_ELEMENT_PROCESSORS = 'registerElementProcessors';
    public const EVENT_REGISTER_SUPPORTED_FIELDS = 'registerSupportedFields';

    /**
     * Get all distinct field layouts from a set of elements
     *
     * @param $elementIds
     * @return FieldWrapper[] fields
     * @throws \Exception
     */
    public function getFieldsForElementIds($elementIds, $elementType)
    {
        // Works for entries
        $processor = $this->getElementTypeProcessor($elementType);
        if (!$processor) {
            throw new \Exception('Unable to process element type');
        }
        $layouts = $processor::getLayoutsFromElementIds($elementIds);

        $fields = [];
        /** @var FieldLayout $layout */
        foreach ($layouts as $layout) {
            $layoutFields = \Craft::$app->fields->getFieldsByLayoutId($layout->id);
            /** @var Field $layoutField */
            foreach ($layoutFields as $layoutField) {
                if (!array_key_exists($layoutField->handle, $fields)) {
                    $fieldWrapper = new FieldWrapper();
                    $fieldWrapper->field = $layoutField;
                    $fieldWrapper->layouts[] = $layout;
                    $fields[$layoutField->handle] = $fieldWrapper;
                } else {
                    $fields[$layoutField->handle]->layouts[] = $layout;
                }
            }
        }
        return $fields;
    }


    /**
     * @param $id
     * @return EditContext|null
     */
    public function getBulkEditContextFromId($id): ?EditContext
    {
        return EditContext::findOne($id);
    }

    /**
     * Gets all unique elements from incomplete bulk edit tasks
     *
     * @param EditContext $context
     * @return int[] pending element IDs
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
     * Gets all pending bulk edit tasks
     *
     * @param EditContext $context
     * @return \yii\db\ActiveQueryInterface
     */
    public function getPendingHistoryFromContext(EditContext $context): \yii\db\ActiveQueryInterface
    {
        return $context->getHistoryItems()->where(['=', 'status', 'pending']);
    }

    /**
     * Gets all pending tasks for a particular element
     *
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
     * @param Element $element
     * @return Element
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function processHistoryItemsForElement($historyItems, Element $element): ?Element
    {
        // We'll process the entire element in a transaction to help avoid problems
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            /** @var History $historyItem */
            foreach ($historyItems as $historyItem) {
                $fieldHandle = $historyItem->field->handle;
                $newValue = \GuzzleHttp\json_decode($historyItem->newValue, true);
                $originalValue = $element->getFieldValue($historyItem->field->handle);
                $historyItem->originalValue = \GuzzleHttp\json_encode($originalValue);
                $historyItem->status = 'completed';
                $field = \Craft::$app->fields->getFieldByHandle($fieldHandle);
                switch($historyItem->strategy) {
                    case 'replace':
                        $element->setFieldValue($fieldHandle, $newValue);
                        break;
                    case 'merge':
                        if ($field && $field instanceof BaseRelationField) {
                            $ids = $originalValue->ids();
                            $ids = array_merge($ids, $newValue);
                            $element->setFieldValue($fieldHandle, $ids);

                        } else {
                            throw new \Exception("Can't merge field: ". $fieldHandle);
                        }
                        break;
                    case 'subtract':
                        if ($field && $field instanceof BaseRelationField) {
                            $ids = $originalValue->ids();
                            $ids = array_diff($ids, $newValue);
                            $element->setFieldValue($fieldHandle, $ids);

                        } else {
                            throw new \Exception("Can't merge field: ". $fieldHandle);
                        }
                        break;

                }
                $historyItem->save();
                Craft::info('Saved history item', __METHOD__);
            }
            $element->setScenario(Element::SCENARIO_ESSENTIALS);
            \Craft::$app->elements->saveElement($element, false);

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

        // Add support for redactor
        if (\Craft::$app->getPlugins()->isPluginEnabled('redactor')) {
            $supportedFields[] = RedactorField::class;
        }

        $event = new RegisterComponentTypesEvent();
        $event->types = &$supportedFields;
        $this->trigger(self::EVENT_REGISTER_SUPPORTED_FIELDS, $event);

        foreach ($supportedFields as $fieldItem) {
            if ($field instanceof $fieldItem) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets an array of values for supported strategies on field types
     * @param FieldInterface $field
     * @return array
     */
    public function getSupportedStrategiesForField(FieldInterface $field)
    {
        $availableStrategies = [
            ['value' => self::STRATEGY_REPLACE, 'label' => 'Replace']
        ];

        if ($field instanceof BaseRelationField) {
            $availableStrategies[] = ['value' => self::STRATEGY_MERGE, 'label' => 'Merge'];
            $availableStrategies[] = ['value' => self::STRATEGY_SUBTRACT, 'label' => 'Subtract'];
        }

        return $availableStrategies;
    }

    /**
     * @param $elementType
     * @return AbstractElementTypeProcessor
     */
    public function getElementTypeProcessor($elementType) {
        $processors = [
            EntryProcessor::class,
            UserProcessor::class,
            CategoryProcessor::class,
            AssetProcessor::class,
        ];

        $event = new RegisterComponentTypesEvent();
        $event->types = &$processors;
        $this->trigger(self::EVENT_REGISTER_ELEMENT_PROCESSORS, $event);

        $processorsKeyedByClass = [];
        foreach($processors as $processor) {
            $reflection = new \ReflectionClass($processor);
            /** @var AbstractElementTypeProcessor $instance */
            $instance = $reflection->newInstanceWithoutConstructor();
            $type = $instance::getType();
            $processorsKeyedByClass[$type] = $processor;
        }

        if (array_key_exists($elementType, $processorsKeyedByClass)) {
            return $processorsKeyedByClass[$elementType];
        }
        return null;
    }
}
