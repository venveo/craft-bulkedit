<?php

namespace venveo\bulkedit\base;

abstract class AbstractElementTypeProcessor implements ElementTypeProcessorInterface
{
    public static function getEditableAttributes(): array
    {
        return [];
    }

}