<?php

namespace venveo\bulkedit\fields\processors;

use craft\base\Element;
use craft\base\Field;
use craft\fields\Number;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\services\BulkEdit;

class NumberFieldProcessor extends AbstractFieldProcessor
{

    /**
     * The fully qualified class name for the element this processor works on
     * @return array
     */
    public static function getSupportedFields(): array
    {
        return [
            Number::class
        ];
    }

    /**
     * Returns the supported strategies for this field type
     * @return array
     */
    public static function getSupportedStrategies(): array
    {
        return [BulkEdit::STRATEGY_REPLACE, BulkEdit::STRATEGY_SUBTRACT, BulkEdit::STRATEGY_ADD, BulkEdit::STRATEGY_MULTIPLY, BulkEdit::STRATEGY_DIVIDE];
    }

    public static function performAddition(Element $element, Field $field, $value)
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);
        $value = $value['value'] ?? 0;
        $element->setFieldValue($fieldHandle, $originalValue + (int)$value);
    }

    public static function performSubtraction(Element $element, Field $field, $value)
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);

        $value = $value['value'] ?? 0;
        $element->setFieldValue($fieldHandle, $originalValue - (int)$value);
    }

    public static function performMultiplication(Element $element, Field $field, $value)
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);

        $value = $value['value'] ?? 0;
        $element->setFieldValue($fieldHandle, $originalValue * (int)$value);
    }

    public static function performDivision(Element $element, Field $field, $value)
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);

        $value = $value['value'] ?? 1;
        $element->setFieldValue($fieldHandle, $originalValue / (int)$value);
    }
}
