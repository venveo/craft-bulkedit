<?php

namespace venveo\bulkedit\enums;

/**
 * Helps BulkEdit determine how to properly save a value
 */
abstract class FieldType
{
    public const CustomField = 'CustomField';
    public const NativeField = 'NativeField';
    public const ElementProperty = 'ElementProperty';

    public static function asArray(): array {
        return [
            static::CustomField,
            static::NativeField,
            static::ElementProperty
        ];
    }
}