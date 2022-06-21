<?php

namespace venveo\bulkedit\fields\processors;

use Craft;
use craft\fieldlayoutelements\TextField;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\fields\strategies\Replace;

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

    public static function getSupportedNativeFields(): array
    {
        return [TextField::class];
    }


    /**
     * @inheritDoc
     */
    public static function getSupportedStrategies(): array
    {
        return [Replace::class];
    }
}
