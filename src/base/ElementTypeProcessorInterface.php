<?php

namespace venveo\bulkedit\base;

use craft\base\Element;
use craft\models\FieldLayout;
use craft\web\User;

interface ElementTypeProcessorInterface
{
    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return FieldLayout[]
     */
    public static function getLayoutsFromElementIds($elementIds): array;

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     * @param $elementIds
     * @param $user
     */
    public static function hasPermission($elementIds, User $user): bool;

    /**
     * The fully qualified class name for the element this processor works on
     */
    public static function getType(): string;

    public static function getEditableAttributes(): array;

    public static function getMockElement($elementIds = [], $params = []): Element;
}
