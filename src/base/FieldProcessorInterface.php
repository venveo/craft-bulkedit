<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\base\Field;

interface FieldProcessorInterface
{
    /**
     * An array of class names for supported fields
     */
    public static function getSupportedFields(): array;
    
    public static function getSupportedNativeFields(): array;

    /**
     * Returns the supported strategies for this field type
     * @return string[]
     */
    public static function getSupportedStrategies(): array;

    public static function performReplacement(Element $element, Field $field, $value);

    public static function performSubtraction(Element $element, Field $field, $value);

    public static function performMerge(Element $element, Field $field, $value);
}
