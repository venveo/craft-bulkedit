<?php

namespace venveo\bulkedit\fields\strategies;

use venveo\bulkedit\base\FieldStrategyInterface;

class Subtract implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return 'Subtract';
    }
}