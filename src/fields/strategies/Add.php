<?php

namespace venveo\bulkedit\fields\strategies;

use venveo\bulkedit\base\FieldStrategyInterface;

class Add implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return 'Add';
    }
}