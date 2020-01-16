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
    public $label;

    public function init()
    {
        if ($this->label === null) {
            $this->label = 'Bulk Edit';
        }
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
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);


        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            return \$selectedItems.length > 1;
        },
        activate: function(\$selectedItems)
        {
            var settings = {};
            var elementIds = $(\$selectedItems).map(function(){
                return $(this).data('id');
            }).get();
            var modal = new Craft.BulkEditModal(elementIds, settings);
        }
    });
})();
EOD;


        $view = Craft::$app->getView();
        $view->registerJs($js);
        $view->registerAssetBundle(BulkEditElementActionAsset::class);
    }
}
