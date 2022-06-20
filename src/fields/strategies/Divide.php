<?php

namespace venveo\bulkedit\fields\strategies;

use venveo\bulkedit\base\FieldStrategyInterface;

class Divide implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return 'Divide';
    }
}