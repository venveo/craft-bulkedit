<?php
namespace venveo\bulkedit\base;

interface ElementTypeProcessorInterface {

    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return array
     */
    public static function getLayoutsFromElementIds($elementIds): array;

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string;
}