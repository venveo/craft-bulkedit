<?php

namespace venveo\bulkedit\fields\strategies;

use venveo\bulkedit\base\FieldStrategyInterface;

class Merge implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return 'Merge';
    }
}