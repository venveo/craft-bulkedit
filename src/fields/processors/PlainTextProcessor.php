<?php

namespace venveo\bulkedit\fields\processors;

use craft\base\Element;
use craft\base\Field;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Lightswitch;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Table;
use craft\redactor\Field as RedactorField;
use craft\fields\Url;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\services\BulkEdit;

class PlainTextProcessor extends AbstractFieldProcessor
{

    /**
     * The fully qualified class name for the element this processor works on
     * @return array
     */
    public static function getSupportedFields(): array
    {
        $fields = [
            PlainText::class,
            Number::class,
            Color::class,
            Checkboxes::class,
            Dropdown::class,
            Date::class,
            Table::class,
            RadioButtons::class,
            Lightswitch::class,
            Url::class,
            Email::class,
            MultiSelect::class
        ];

        if (\Craft::$app->plugins->isPluginInstalled('redactor')) {
            $fields[] = RedactorField::class;
        }

        return $fields;
    }

    /**
     * Returns the supported strategies for this field type
     * @return array
     */
    public static function getSupportedStrategies(): array
    {
        return [BulkEdit::STRATEGY_REPLACE];
    }
}
