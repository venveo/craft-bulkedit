<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\base\Field;
use craft\base\FieldInterface;
use venveo\bulkedit\services\BulkEdit;

abstract class AbstractFieldProcessor implements FieldProcessorInterface
{
    public static function performMerge(Element $element, Field $field, $value): void
    {
        throw new \RuntimeException('Merge not implemented for this field type');
    }

    public static function performReplacement(Element $element, Field $field, $value): void
    {
        $fieldHandle = $field->handle;
        $element->setFieldValue($fieldHandle, $value);
    }

    public static function performSubtraction(Element $element, Field $field, $value): void
    {
        throw new \RuntimeException('Subtraction not implemented for this field type');
    }

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

    public static function processElementField(Element $element, Field $field, $strategy, $newValue): void
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
        }
    }
}