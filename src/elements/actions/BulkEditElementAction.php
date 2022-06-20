<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2018 André Elvan
 */

namespace venveo\bulkedit\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;
use venveo\bulkedit\assetbundles\bulkeditelementaction\BulkEditElementActionAsset;

/**
 *
 * @property void $triggerHtml
 * @property string $triggerLabel
 */
class BulkEditElementAction extends ElementAction
{
    public string $label;

    public function init(): void
    {
        $this->label = 'Bulk Edit';
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }


    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        $type = Json::encode(static::class);


        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            return \$selectedItems.length > 1;
        },
        activate: function(\$selectedItems)
        {
            var modal = new Craft.BulkEditModal(Craft.elementIndex, {});
        }
    });
})();
JS;


        $view = Craft::$app->getView();
        $view->registerJs($js);
        $view->registerAssetBundle(BulkEditElementActionAsset::class);
        return null;
    }
}
