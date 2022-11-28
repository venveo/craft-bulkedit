<?php

namespace venveo\bulkedit\base;

use Craft;
use craft\base\Element;

abstract class AbstractElementTypeProcessor implements ElementTypeProcessorInterface
{
    /**
     * @return mixed[]
     */
    public static function getEditableAttributes(): array
    {
        return [];
    }

    public static function getMockElement($elementIds = [], $params = []): Element
    {
        $params['type'] = static::getType();
        return Craft::$app->elements->createElement($params);
    }
}
