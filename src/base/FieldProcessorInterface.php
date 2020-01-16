<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\base\Field;

interface FieldProcessorInterface
{
    /**
     * An array of class names for supported fields
     * @return array
     */
    public static function getSupportedFields(): array;

    /**
     * Returns the supported strategies for this field type
     * @return array
     */
    public static function getSupportedStrategies(): array;

    public static function performReplacement(Element $element, Field $field, $value);

    public static function performSubtraction(Element $element, Field $field, $value);

    public static function performMerge(Element $element, Field $field, $value);
}