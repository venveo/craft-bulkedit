<?php

namespace venveo\bulkedit\elements\processors;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\records\FieldLayout;
use craft\web\User;
use venveo\bulkedit\base\AbstractElementTypeProcessor;
use venveo\bulkedit\Plugin;

class AssetProcessor extends AbstractElementTypeProcessor
{

    /**
     * Gets a unique list of field layouts from a list of element IDs
     * @param $elementIds
     * @return array
     */
    public static function getLayoutsFromElementIds($elementIds): array
    {
        $volumeIds = \craft\records\Asset::find()
            ->select('volumeId')
            ->distinct(true)
            ->limit(null)
            ->where(['IN', '[[id]]', $elementIds])
            ->column();

        $fieldLayouts = [];
        foreach ($volumeIds as $volumeId) {
            /** @var Volume $volume */
            $volume = Craft::$app->volumes->getVolumeById($volumeId);
            if (!$volume->fieldLayoutId) {
                continue;
            }
            $fieldLayout = $volume->getFieldLayout();
            if ($fieldLayout) {
                $fieldLayouts[] = $fieldLayout;
            }
        }

        return $fieldLayouts;
    }

    /**
     * The fully qualified class name for the element this processor works on
     * @return string
     */
    public static function getType(): string
    {
        return Asset::class;
    }

    /**
     * Return whether a given user has permission to perform bulk edit actions on these elements
     * @param $elementIds
     * @param $user
     * @return bool
     */
    public static function hasPermission($elementIds, User $user): bool
    {
        return $user->checkPermission(Plugin::PERMISSION_BULKEDIT_ASSETS);
    }

    public static function getMockElement($elementIds = [], $params = []): Element
    {
        $asset = Craft::$app->assets->getAssetById($elementIds[0]);
        /** @var Asset $elementPlaceholder */
        $elementPlaceholder = Craft::createObject(static::getType(), $params);
        // Field availability is determined by volume ID
        $elementPlaceholder->volumeId = $asset->volumeId;
        return $elementPlaceholder;
    }
}