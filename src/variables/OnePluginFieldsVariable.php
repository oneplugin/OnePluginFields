<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\variables;

use oneplugin\onepluginfields\OnePluginFields;
use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;
use craft\web\View;
/**
 * OnePluginFields Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.onePluginFields }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Jagadeesh Vijayakumar
 * @package   OnePluginFields
 * @since     1.0.0
 */
class OnePluginFieldsVariable
{
    // Public Methods
    // =========================================================================
    const DEV_MODE = false;

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
        if( self::DEV_MODE ){
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
        if( self::DEV_MODE ){
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
            Craft::$app->getView()->registerCssFile($cssFile,['position' => View::POS_END,'defer' => true]);
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['position' => View::POS_END,'defer' => true]);
        }

        return TemplateHelper::raw(implode(PHP_EOL,[]));
    }
}
