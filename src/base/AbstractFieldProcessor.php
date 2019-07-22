<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\base\Field;
use craft\base\FieldInterface;

abstract class AbstractFieldProcessor implements FieldProcessorInterface
{
    public static function performMerge(Element $element, Field $field, $value): void
    {

    }

    public static function performReplacement(Element $element, Field $field, $value): void
    {

    }

    public static function performSubtraction(Element $element, Field $field, $value): void
    {

    }

    /**
     * @param FieldInterface $field
     * @return bool
     */
    public static function supportsField(FieldInterface $field): bool
    {
        foreach (self::getSupportedFields() as $fieldType) {
            if ($field instanceof $fieldType) {
                return true;
            }
        }
        return false;
    }
}