<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\variables;

use Craft;
use craft\web\View;
use craft\helpers\Template as TemplateHelper;
use oneplugin\onepluginfields\OnePluginFields;

class OnePluginFieldsVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param bool $includeJQuery
     * @param bool $includePDFJS
     *
     * @throws \yii\base\InvalidConfigException
     */

    public function includeAssets($jquery = false,$pdfJS = false,$mapJS = false)
    {
        $settings = OnePluginFields::$plugin->getSettings();

        $folder = 'dist';
        if( OnePluginFields::$devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/' . $folder,
            true
        );

        $cssFiles = [];//[$baseAssetsUrl . '/css/onepluginfields.min.css'];
        $jsFiles = [];
        if( $jquery ){
            $jsFiles[] = $baseAssetsUrl . '/js/jquery.min.js';
        }
        if( OnePluginFields::$devMode ){
            $jsFiles = array_merge($jsFiles,[ $baseAssetsUrl . '/js/icons/lottie_svg.js',$baseAssetsUrl . '/js/icons/onepluginfields-lottie.js']);
            if( $pdfJS ){
                $jsFiles[] = $baseAssetsUrl . '/js/pdf/pdf.js';
                $jsFiles[] = $baseAssetsUrl . '/js/pdf/pdf.embed.js';
                $jsFiles[] = $baseAssetsUrl . '/js/pdf/pdf.worker.js';
            }
            if( !empty($settings->mapsAPIKey) && $mapJS){
                $jsFiles[] = 'https://maps.googleapis.com/maps/api/js?key=' . $settings->mapsAPIKey . '&libraries=places&v=3.exp';
                $jsFiles[] = $baseAssetsUrl . '/js/map/map.js';
            }
        }
        else{
            $jsFiles = array_merge($jsFiles,[ $baseAssetsUrl . '/js/onepluginfields-lottie.min.js']);
            if( $pdfJS ){
                $jsFiles[] = $baseAssetsUrl . '/js/pdf.min.js';
            }
            if( !empty($settings->mapsAPIKey) && $mapJS){
                $jsFiles[] = 'https://maps.googleapis.com/maps/api/js?key=' . $settings->mapsAPIKey . '&libraries=places&v=3.exp';
                $jsFiles[] = $baseAssetsUrl . '/js/map.min.js';
            }
        }

        foreach ($cssFiles as $cssFile) {
            Craft::$app->getView()->registerCssFile($cssFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$cssFile));
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$jsFile) );
        }

        return TemplateHelper::raw(implode(PHP_EOL,[]));
    }
}
