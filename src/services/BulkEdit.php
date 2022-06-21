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
use craft\elements\db\ElementQueryInterface;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\BaseField;
use craft\helpers\Json;
use craft\models\FieldLayout;
use Exception;
use ReflectionClass;
use ReflectionException;
use Throwable;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\base\ElementTypeProcessorInterface;
use venveo\bulkedit\base\FieldProcessorInterface;
use venveo\bulkedit\elements\processors\AssetProcessor;
use venveo\bulkedit\elements\processors\CategoryProcessor;
use venveo\bulkedit\elements\processors\EntryProcessor;
use venveo\bulkedit\elements\processors\ProductProcessor;
use venveo\bulkedit\elements\processors\UserProcessor;
use venveo\bulkedit\fields\processors\NumberFieldProcessor;
use venveo\bulkedit\fields\processors\PlainTextProcessor;
use venveo\bulkedit\fields\processors\RelationFieldProcessor;
use venveo\bulkedit\models\AttributeWrapper;
use venveo\bulkedit\models\EditContext;
use venveo\bulkedit\models\FieldWrapper;
use venveo\bulkedit\queue\jobs\SaveBulkEditJob;

/**
 * @author    Venveo
 * @package   BulkEdit
 * @since     1.0.0
 *
 * @property-read string[] $elementTypeProcessors
 * @property-read string[] $fieldProcessors
 * @property-read mixed[] $supportedFieldTypes
 */
class BulkEdit extends Component
{
    /**
     * @var string
     */
    public const EVENT_REGISTER_ELEMENT_PROCESSORS = 'registerElementProcessors';

    /**
     * @var string
     */
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
     * @throws ReflectionException
     * @throws Exception
     */
    public function getFieldWrappersForElementQuery(ElementQueryInterface $query): array
    {
        $elementType = $query->elementType;
        // Works for entries
        /** @var ElementTypeProcessorInterface $processor */
        $processor = $this->getElementTypeProcessor($elementType);
        if (!$processor) {
            throw new Exception('Unable to process element type');
        }
        if ($query->id) {
            $elementIds = is_array($query->id) ? $query->id : [$query->id];
            $layouts = $processor::getLayoutsFromElementIds($elementIds);
        } else {
            $layouts = Craft::$app->getFields()->getLayoutsByType($elementType);
        }

        $fields = [];
        /** @var FieldLayout $layout */
        foreach ($layouts as $layout) {
            $layoutFields = $layout->getCustomFields();
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
     * @return \venveo\bulkedit\models\AttributeWrapper[]
     */
    public function getAttributeWrappersForElementQuery(ElementQueryInterface $query): array
    {
        $elementType = $query->elementType;

        /** @var ElementTypeProcessorInterface $processor */
        $processor = $this->getElementTypeProcessor($elementType);
        if (!$processor) {
            throw new Exception('Unable to process element type');
        }

        $attributes = $processor::getEditableAttributes();
        return array_map(fn($attribute) => new AttributeWrapper([
            'handle' => $attribute['handle'],
            'name' => $attribute['name'],
        ]), $attributes);
    }

    /**
     * Retrieves the processor for a type of element. The processor determines how to do things like get the field
     * layout.
     * @param $elementType
     * @return string processor classname
     * @throws ReflectionException
     */
    public function getElementTypeProcessor($elementType)
    {
        $processors = $this->getElementTypeProcessors();

        $processorsKeyedByClass = [];
        foreach ($processors as $processor) {
            $reflection = new ReflectionClass($processor);
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
     * Takes an array of history changes for a particular element and saves it to that element.
     *
     * @return Element
     * @throws Throwable
     * @throws \yii\base\Exception
     */
    public function processElementWithContext(Element $element, EditContext $contextModel)
    {
        // We'll process the entire element in a transaction to help avoid problems
        $transaction = Craft::$app->getDb()->beginTransaction();
        $fieldConfigs = $contextModel->fieldConfigs;
        try {
            foreach ($fieldConfigs as $fieldConfig) {
                $newValue = Json::decode($fieldConfig->serializedValue);
                $field = Craft::$app->fields->getFieldById($fieldConfig->fieldId);
                $processor = $this->getFieldProcessor($field, $fieldConfig->strategy);
                $processor::processElementField($element, $field, $fieldConfig->strategy, $newValue);
                Craft::info('Saved history item', __METHOD__);
            }

            $element->setScenario(Element::SCENARIO_ESSENTIALS);
            Craft::$app->elements->saveElement($element, false);

            Craft::info('Saved element', __METHOD__);
            $transaction->commit();
            return $element;
        } catch (Exception $exception) {
            $transaction->rollBack();
            Craft::error('Transaction rolled back', __METHOD__);
            throw $exception;
        }
    }

    /**
     * Retrieves the processor for a type of field
     * @param $strategy
     * @return AbstractFieldProcessor|null field processor class
     * @throws ReflectionException
     */
    public function getFieldProcessor(FieldInterface $fieldType, $strategy = null)
    {
        $processors = $this->getFieldProcessors();

        foreach ($processors as $processor) {
            $reflection = new ReflectionClass($processor);
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
     * @return FieldProcessorInterface[]
     */
    public function getFieldProcessors(): array
    {
        if (self::$_FIELD_TYPE_PROCESSORS !== null) {
            return self::$_FIELD_TYPE_PROCESSORS;
        }

        $processors = [
            PlainTextProcessor::class,
            RelationFieldProcessor::class,
            NumberFieldProcessor::class,
        ];

        $event = new RegisterComponentTypesEvent();
        $event->types = &$processors;
        $this->trigger(self::EVENT_REGISTER_FIELD_PROCESSORS, $event);

        self::$_FIELD_TYPE_PROCESSORS = $processors;
        return $processors;
    }

    public function isFieldSupported(FieldInterface|BaseField $field, $strategy = null): bool
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
     * Gets a general list of all field types supported by the strategies we have
     * @return mixed[]
     */
    public function getSupportedFieldTypes(): array
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
     * Gets an array of values for supported strategies on field types. This is used by the _fields template
     * @return array<int, array{value: string, label: string}>
     */
    public function getSupportedStrategiesForField(FieldInterface $field): array
    {
        $processorsList = $this->getProcessorsKeyedByStrategyForField($field);

        $availableStrategies = array_map(function ($strategy) {
            return [
                'value' => $strategy,
                'label' => $strategy::displayName()
            ];
        }, array_keys($processorsList));

        return $availableStrategies;
    }

    /**
     *
     * @return array<int|string, \venveo\bulkedit\base\AbstractFieldProcessor[]>
     */
    public function getProcessorsKeyedByStrategyForField(FieldInterface $field): array
    {
        $processors = $this->getFieldProcessors();

        $processorsByStrategy = [];

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

    /**
     * Saves an element context
     * @param $elementType
     * @param $siteId
     * @param $elementIds
     * @param $fieldConfigs
     * @throws ReflectionException
     * @throws Throwable
     */
    public function saveContext(
        $elementType,
        $siteId,
        $elementIds,
        $fieldConfigs
    ): void {
        /** @var AbstractElementTypeProcessor $processor */
        $processor = $this->getElementTypeProcessor($elementType);

        if (!$processor::hasPermission($elementIds, Craft::$app->user)) {
            throw new Exception('Missing permissions');
        }

        $contextModel = new EditContext();
        $contextModel->fieldConfigs = $fieldConfigs;
        $contextModel->elementIds = $elementIds;
        $contextModel->siteId = $siteId;
        $contextModel->ownerId = Craft::$app->getUser()->getIdentity()->id;
        $contextModel->total = count($elementIds);
        
        $job = new SaveBulkEditJob([
            'context' => $contextModel,
        ]);
        Craft::$app->getQueue()->push($job);
    }
}
