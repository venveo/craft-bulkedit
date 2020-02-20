<?php

namespace venveo\bulkedit\base;

use Craft;
use craft\base\Element;

abstract class AbstractElementTypeProcessor implements ElementTypeProcessorInterface
{
    public static function getEditableAttributes(): array
    {
        return [];
    }

    public static function getMockElement($elementIds = [], $params = []): Element
    {
        /** @var Element $elementPlaceholder */
        $elementPlaceholder = Craft::createObject(static::getType(), $params);
        return $elementPlaceholder;
    }
}