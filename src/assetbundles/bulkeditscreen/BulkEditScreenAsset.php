<?php
namespace venveo\bulkedit\assetbundles\bulkeditscreen;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class BulkEditScreenAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@venveo/bulkedit/assetbundles/bulkeditscreen/dist';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/BulkEditScreen.js',
        ];

        $this->css = [
            'css/BulkEditScreen.css',
        ];

        parent::init();
    }
}
