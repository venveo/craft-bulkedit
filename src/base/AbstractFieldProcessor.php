<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\base\Field;
use craft\base\FieldInterface;
use RuntimeException;
use venveo\bulkedit\services\BulkEdit;

abstract class AbstractFieldProcessor implements FieldProcessorInterface
{
    /**
     * @param FieldInterface $field
     * @return bool
     */
    public static function supportsField(FieldInterface $field): bool
    {
        foreach (static::getSupportedFields() as $fieldType) {
            if ($field instanceof $fieldType) {
                return true;
            }
        }
        return false;
    }

    public static function processElementField(Element $element, Field $field, $strategy, $newValue)
    {
        switch ($strategy) {
            case BulkEdit::STRATEGY_REPLACE:
                static::performReplacement($element, $field, $newValue);
                break;
            case BulkEdit::STRATEGY_MERGE:
                static::performMerge($element, $field, $newValue);
                break;
            case BulkEdit::STRATEGY_SUBTRACT:
                static::performSubtraction($element, $field, $newValue);
                break;
            case BulkEdit::STRATEGY_ADD:
                static::performAddition($element, $field, $newValue);
                break;
            case BulkEdit::STRATEGY_MULTIPLY:
                static::performMultiplication($element, $field, $newValue);
                break;
            case BulkEdit::STRATEGY_DIVIDE:
                static::performDivision($element, $field, $newValue);
                break;
        }
    }

    public static function performReplacement(Element $element, Field $field, $value)
    {
        $fieldHandle = $field->handle;
        $element->setFieldValue($fieldHandle, $value);
    }

    public static function performMerge(Element $element, Field $field, $value)
    {
        throw new RuntimeException('Merge not implemented for this field type');
    }

    public static function performSubtraction(Element $element, Field $field, $value)
    {
        throw new RuntimeException('Subtraction not implemented for this field type');
    }

    public static function performAddition(Element $element, Field $field, $value)
    {
        throw new RuntimeException('Addition not implemented for this field type');
    }

    public static function performMultiplication(Element $element, Field $field, $value)
    {
        throw new RuntimeException('Multiplication not implemented for this field type');
    }

    public static function performDivision(Element $element, Field $field, $value)
    {
        throw new RuntimeException('Division not implemented for this field type');
    }
}