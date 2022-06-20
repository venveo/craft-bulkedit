<?php

namespace venveo\bulkedit\fields\strategies;

use venveo\bulkedit\base\FieldStrategyInterface;

class Replace implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return 'Replace';
    }
}