<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\base\Field;
use craft\base\FieldInterface;
use RuntimeException;
use venveo\bulkedit\fields\strategies\Add;
use venveo\bulkedit\fields\strategies\Divide;
use venveo\bulkedit\fields\strategies\Merge;
use venveo\bulkedit\fields\strategies\Multiply;
use venveo\bulkedit\fields\strategies\Replace;
use venveo\bulkedit\fields\strategies\Subtract;

abstract class AbstractFieldProcessor implements FieldProcessorInterface
{
    public static function supportsField(FieldInterface $field): bool
    {
        foreach (static::getSupportedFields() as $fieldType) {
            if ($field instanceof $fieldType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getSupportedNativeFields(): array
    {
        return [];
    }

    public static function processElementField(Element $element, Field $field, string $strategy, mixed $newValue)
    {
        match ($strategy) {
            Replace::class => static::performReplacement($element, $field, $newValue),
            Merge::class => static::performMerge($element, $field, $newValue),
            Subtract::class => static::performSubtraction($element, $field, $newValue),
            Add::class => static::performAddition($element, $field, $newValue),
            Multiply::class => static::performMultiplication($element, $field, $newValue),
            Divide::class => static::performDivision($element, $field, $newValue),
        };
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
