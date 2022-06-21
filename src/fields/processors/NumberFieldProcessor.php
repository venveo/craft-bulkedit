<?php

namespace venveo\bulkedit\fields\processors;

use craft\base\Element;
use craft\base\Field;
use craft\fields\Number;
use venveo\bulkedit\base\AbstractFieldProcessor;
use venveo\bulkedit\fields\strategies\Add;
use venveo\bulkedit\fields\strategies\Divide;
use venveo\bulkedit\fields\strategies\Multiply;
use venveo\bulkedit\fields\strategies\Replace;
use venveo\bulkedit\fields\strategies\Subtract;

class NumberFieldProcessor extends AbstractFieldProcessor
{
    /**
     * The fully qualified class name for the element this processor works on
     * @return array<class-string<\craft\fields\Number>>
     */
    public static function getSupportedFields(): array
    {
        return [
            Number::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getSupportedStrategies(): array
    {
        return [Replace::class, Subtract::class, Add::class, Multiply::class, Divide::class];
    }

    public static function performAddition(Element $element, Field $field, $value): void
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);
        $value = $value['value'] ?? 0;
        $element->setFieldValue($fieldHandle, $originalValue + (int)$value);
    }

    public static function performSubtraction(Element $element, Field $field, $value): void
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);

        $value = $value['value'] ?? 0;
        $element->setFieldValue($fieldHandle, $originalValue - (int)$value);
    }

    public static function performMultiplication(Element $element, Field $field, $value): void
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);

        $value = $value['value'] ?? 0;
        $element->setFieldValue($fieldHandle, $originalValue * (int)$value);
    }

    public static function performDivision(Element $element, Field $field, $value): void
    {
        $fieldHandle = $field->handle;
        $originalValue = (int)$element->getFieldValue($fieldHandle);

        $value = $value['value'] ?? 1;
        $element->setFieldValue($fieldHandle, $originalValue / (int)$value);
    }
}
