<?php

namespace venveo\bulkedit\fields\strategies;

use venveo\bulkedit\base\FieldStrategyInterface;

class Multiply implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return 'Multiply';
    }
}