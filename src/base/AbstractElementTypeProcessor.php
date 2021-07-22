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
        $params['type'] = static::getType();
        $elementPlaceholder = Craft::$app->elements->createElement($params);
        return $elementPlaceholder;
    }
}