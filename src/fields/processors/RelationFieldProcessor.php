<?php

namespace venveo\bulkedit\fields\processors;

use craft\base\Element;
use craft\base\Field;
use craft\fields\BaseRelationField;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\services\BulkEdit;

class RelationFieldProcessor extends AbstractFieldProcessor
{

    /**
     * The fully qualified class name for the element this processor works on
     * @return array<class-string<\craft\fields\BaseRelationField>>
     */
    public static function getSupportedFields(): array
    {
        return [
            BaseRelationField::class
        ];
    }

    /**
     * Returns the supported strategies for this field type
     * @return string[]
     */
    public static function getSupportedStrategies(): array
    {
        return [BulkEdit::STRATEGY_REPLACE, BulkEdit::STRATEGY_SUBTRACT, BulkEdit::STRATEGY_MERGE];
    }

    public static function performSubtraction(Element $element, Field $field, $value): void
    {
        $fieldHandle = $field->handle;
        $originalValue = $element->getFieldValue($fieldHandle);
        $ids = $originalValue->ids();
        $ids = array_diff($ids, $value);

        $element->setFieldValue($fieldHandle, $ids);
    }

    public static function performMerge(Element $element, Field $field, $value): void
    {
        $originalValue = $element->getFieldValue($field->handle);
        $fieldHandle = $field->handle;
        $ids = $originalValue->ids();
        $ids = array_merge($ids, $value);

        $element->setFieldValue($fieldHandle, $ids);
    }
}
