<?php

namespace venveo\bulkedit\fields\processors;

use Craft;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\services\BulkEdit;

class PlainTextProcessor extends AbstractFieldProcessor
{
    /**
     * The fully qualified class name for the element this processor works on
     * @return mixed[]
     */
    public static function getSupportedFields(): array
    {
        return Craft::$app->fields->getAllFieldTypes();
    }

    /**
     * Returns the supported strategies for this field type
     * @return string[]
     */
    public static function getSupportedStrategies(): array
    {
        return [BulkEdit::STRATEGY_REPLACE];
    }
}
