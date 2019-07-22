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
use craft\events\RegisterComponentTypesEvent;
use craft\records\FieldLayout;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\elements\processors\AssetProcessor;
use venveo\bulkedit\elements\processors\CategoryProcessor;
use venveo\bulkedit\elements\processors\EntryProcessor;
use venveo\bulkedit\elements\processors\ProductProcessor;
use venveo\bulkedit\elements\processors\UserProcessor;
use venveo\bulkedit\fields\processors\PlainTextProcessor;
use venveo\bulkedit\fields\processors\RelationFieldProcessor;
use venveo\bulkedit\models\FieldWrapper;
use venveo\bulkedit\queue\jobs\SaveBulkEditJob;
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
    public const EVENT_REGISTER_FIELD_PROCESSORS = 'registerFieldProcessors';

    // Memoized values
    private static $_ELEMENT_TYPE_PROCESSORS;
    private static $_FIELD_TYPE_PROCESSORS;

    /**
     * Get all distinct field layouts from a set of elements
     *
     * @param $elementIds
     * @param $elementType
     * @return FieldWrapper[] fields
     * @throws \ReflectionException
     */
    public function getFieldsForElementIds($elementIds, $elementType): array
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
     * Gets all unique elements from incomplete bulk edit jobs
     *
     * @param EditContext $context
     * @return \yii\db\ActiveQuery
     */
    public function getPendingElementsHistoriesFromContext(EditContext $context): \yii\db\ActiveQuery
    {
        $items = History::find()
            ->limit(null)
            ->where(['=', 'contextId', $context->id])
            ->andWhere(['=', 'status', 'pending'])->indexBy('elementId');

        return $items;
    }

    /**
     * Gets all pending bulk edit changes for a particular job
     *
     * @param EditContext $context
     * @return \yii\db\ActiveQueryInterface
     */
    public function getPendingHistoryFromContext(EditContext $context, $elementId = null): \yii\db\ActiveQueryInterface
    {
        $query = $context->getHistoryItems()->where(['=', 'status', 'pending']);
        if ($elementId !== null) {
            $query->where(['=', 'elementId', $elementId]);
        }
        return $query;
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

                // Store a snapshot of the original field value
                $historyItem->originalValue = \GuzzleHttp\json_encode($originalValue);
                $historyItem->status = 'completed';

                $field = \Craft::$app->fields->getFieldByHandle($fieldHandle);

                $processor = $this->getFieldProcessor($field, $historyItem->strategy);
                $processor::processElementField($element, $field, $historyItem->strategy, $newValue);
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

    /**
     * Gets a general list of all field types supported by the strategies we have
     * @return array
     */
    public function getSupportedFieldTypes()
    {
        $fieldProcessors = $this->getFieldProcessors();
        $supportedFields = [];
        /** @var AbstractFieldProcessor $fieldProcessor */
        foreach ($fieldProcessors as $fieldProcessor) {
            $fields = $fieldProcessor::getSupportedFields();
            $supportedFields = array_merge($supportedFields, $fields);
        }
        return $supportedFields;
    }

    /**
     *
     * @param FieldInterface $field
     * @return array
     */
    public function getProcessorsKeyedByStrategyForField(FieldInterface $field)
    {
        $processors = $this->getFieldProcessors();

        $processorsByStrategy = [];

        /** @var AbstractFieldProcessor $processor */
        foreach ($processors as $processor) {
            if (!$processor::supportsField($field)) {
                continue;
            }
            foreach ($processor::getSupportedStrategies() as $strategy) {
                $processorsByStrategy[$strategy][] = $processor;
            }
        }
        return $processorsByStrategy;
    }

    public function isFieldSupported(FieldInterface $field, $strategy = null): bool
    {
        $supportedFields = $this->getSupportedFieldTypes();

        foreach ($supportedFields as $fieldItem) {
            if ($field instanceof $fieldItem) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets an array of values for supported strategies on field types. This is used by the _fields template
     * @param FieldInterface $field
     * @return array
     */
    public function getSupportedStrategiesForField(FieldInterface $field): array
    {
        $processorsList = $this->getProcessorsKeyedByStrategyForField($field);

        $availableStrategies = [];
        foreach ($processorsList as $strategy => $processors) {
            switch ($strategy) {
                case self::STRATEGY_REPLACE:
                    $availableStrategies[] = ['value' => self::STRATEGY_REPLACE, 'label' => 'Replace'];
                    break;
                case self::STRATEGY_MERGE:
                    $availableStrategies[] = ['value' => self::STRATEGY_MERGE, 'label' => 'Merge'];
                    break;
                case self::STRATEGY_SUBTRACT:
                    $availableStrategies[] = ['value' => self::STRATEGY_SUBTRACT, 'label' => 'Subtract'];
                    break;
            }
        }


        return $availableStrategies;
    }

    /**
     * Retrieves the processor for a type of element. The processor determines how to do things like get the field
     * layout.
     * @param $elementType
     * @return string processor classname
     * @throws \ReflectionException
     */
    public function getElementTypeProcessor($elementType): ?string
    {
        $processors = $this->getElementTypeProcessors();

        $processorsKeyedByClass = [];
        foreach ($processors as $processor) {
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

    /**
     * Gets an array of all element type processors
     * @return array
     */
    public function getElementTypeProcessors(): array
    {
        if (self::$_ELEMENT_TYPE_PROCESSORS !== null) {
            return self::$_ELEMENT_TYPE_PROCESSORS;
        }

        $processors = [
            EntryProcessor::class,
            UserProcessor::class,
            CategoryProcessor::class,
            AssetProcessor::class,
        ];

        if (Craft::$app->plugins->isPluginInstalled('commerce')) {
            $processors[] = ProductProcessor::class;
        }

        $event = new RegisterComponentTypesEvent();
        $event->types = &$processors;
        $this->trigger(self::EVENT_REGISTER_ELEMENT_PROCESSORS, $event);
        self::$_ELEMENT_TYPE_PROCESSORS = $processors;
        return $processors;
    }

    /**
     * Retrieves the processor for a type of field
     * @param FieldInterface $fieldType
     * @param $strategy
     * @return AbstractFieldProcessor|null field processor class
     * @throws \ReflectionException
     */
    public function getFieldProcessor(FieldInterface $fieldType, $strategy = null): ?AbstractFieldProcessor
    {
        $processors = $this->getFieldProcessors();

        foreach ($processors as $processor) {
            $reflection = new \ReflectionClass($processor);
            /** @var AbstractFieldProcessor $instance */
            $instance = $reflection->newInstanceWithoutConstructor();

            if ($strategy && !in_array($strategy, $instance::getSupportedStrategies(), true)) {
                continue;
            }

            $fields = $instance::getSupportedFields();
            foreach ($fields as $field) {
                if (!$fieldType instanceof $field) {
                    continue;
                }

                return $instance;
            }

        }
    }

    /**
     * Gets an array of all field processors
     * @return array
     */
    public function getFieldProcessors(): array
    {
        if (self::$_FIELD_TYPE_PROCESSORS !== null) {
            return self::$_FIELD_TYPE_PROCESSORS;
        }
        $processors = [
            PlainTextProcessor::class,
            RelationFieldProcessor::class
        ];

        $event = new RegisterComponentTypesEvent();
        $event->types = &$processors;
        $this->trigger(self::EVENT_REGISTER_FIELD_PROCESSORS, $event);

        self::$_FIELD_TYPE_PROCESSORS = $processors;
        return $processors;
    }

    /**
     * Saves an element context
     * @param $elementType
     * @param $siteId
     * @param $elementIds
     * @param $fieldIds
     * @param $keyedFieldValues
     * @param $fieldStrategies
     * @throws \ReflectionException
     * @throws \yii\db\Exception
     */
    public function saveContext($elementType, $siteId, $elementIds, $fieldIds, $keyedFieldValues, $fieldStrategies): void
    {
        /** @var AbstractElementTypeProcessor $processor */
        $processor = $this->getElementTypeProcessor($elementType);

        if (!$processor::hasPermission($elementIds, Craft::$app->user)) {
            throw new \Exception('Missing permissions');
        }

        $context = new EditContext();
        $context->ownerId = \Craft::$app->getUser()->getIdentity()->id;
        $context->siteId = $siteId;
        $context->elementType = $elementType;
        $context->elementIds = \GuzzleHttp\json_encode($elementIds);
        $context->fieldIds = \GuzzleHttp\json_encode($fieldIds);
        $context->save();

        $rows = [];
        foreach ($elementIds as $elementId) {
            foreach ($fieldIds as $fieldId) {
                $strategy = $fieldStrategies[$fieldId] ?? self::STRATEGY_REPLACE;

                $rows[] = [
                    'pending',
                    $context->id,
                    (int)$elementId,
                    (int)$fieldId,
                    (int)$siteId,
                    '[]',
                    \GuzzleHttp\json_encode($keyedFieldValues[$fieldId]),
                    $strategy
                ];
            }
        }

        $cols = ['status', 'contextId', 'elementId', 'fieldId', 'siteId', 'originalValue', 'newValue', 'strategy'];
        \Craft::$app->db->createCommand()->batchInsert(History::tableName(), $cols, $rows)->execute();


        $job = new SaveBulkEditJob([
            'context' => $context
        ]);
        \Craft::$app->getQueue()->push($job);
    }
}
