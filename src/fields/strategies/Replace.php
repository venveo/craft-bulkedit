<?php

namespace venveo\bulkedit\fields\strategies;

use Craft;
use venveo\bulkedit\base\FieldStrategyInterface;

class Replace implements FieldStrategyInterface
{
    public static function displayName(): string
    {
        return Craft::t('venveo-bulk-edit', 'Replace');
    }
}
