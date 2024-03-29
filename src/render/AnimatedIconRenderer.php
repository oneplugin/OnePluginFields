<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\render;

use Craft;
use DOMDocument;
use craft\web\View;
use craft\helpers\UrlHelper;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;
use oneplugin\onepluginfields\records\OnePluginFieldsAnimatedIcon;

class AnimatedIconRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $settings = OnePluginFields::$plugin->getSettings();
        $plugin = OnePluginFields::$plugin;
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $html = '';
        $icon = '';
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        try{
            $url = UrlHelper::actionUrl('one-plugin-fields/one-plugin/load/',[ 'name' => $asset->iconData['asset']['icon-name'],'type' => 'aicon','trigger'=>$asset->iconData['asset']['icon-trigger'] ] );
            $aIcon = $doc->createElement('one-plugin');
            empty($attributes['class']) ?:$this->setAttribute($doc,$aIcon,'class',$attributes['class']);
            if( $attributes['size'] ){
                $this->setAttribute($doc,$aIcon,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }

            $this->setAttribute($doc,$aIcon,'stroke',$asset->iconData['asset']['icon-stroke-width']);
            $this->setAttribute($doc,$aIcon,'colors','primary:' . $asset->iconData['asset']['icon-primary'] . ',secondary:' . $asset->iconData['asset']['icon-secondary']);
            $this->setAttribute($doc,$aIcon,'trigger',$asset->iconData['asset']['icon-trigger']);

            $name = $asset->iconData['asset']['icon-name'];
            $trigger = $asset->iconData['asset']['icon-trigger'];
            $icon_name = $asset->iconData['asset']['icon-name'];
            $icon_name .= '_' . $trigger;
            if( $settings->aIconDataAsHtml ){ //Hidden in Settings now and value set to true
                $icons = OnePluginFieldsAnimatedIcon::find()
                    ->where(['name' => $name])
                    ->all();
                if( count($icons) > 0 ){
                    if( !empty($trigger) && ($trigger == 'morph' || $trigger == 'morph-two-way') ){
                        $icon = $icons[0]['data_morph'];
                    }
                    else{
                        $icon = $icons[0]['data_loop'];
                    }
                }
                $this->setAttribute($doc,$aIcon,'icon',$icon_name);
                if( !empty($icon) ){
                    $aIcon->appendChild($doc->createCDATASection('<data style="display:none">' . $icon . '</data>'));
                }
                else{
                    $this->setAttribute($doc,$aIcon,'src',$url); //fallback, in case :)
                }
            }
            else{
                $this->setAttribute($doc,$aIcon,'src',$url);
            }
            $this->includeAssets();
            return [$this->htmlFromDOMAfterAddingProperties($doc,$aIcon,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }

    public function includeAssets()
    {
        $folder = 'dist';
         if( OnePluginFields::$devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/' . $folder,
            true
        );

        $jsFiles = [];
        $jsFiles[] = $baseAssetsUrl . '/js/jquery.min.js';

        if( OnePluginFields::$devMode ){
            $jsFiles = array_merge($jsFiles,[ $baseAssetsUrl . '/js/icons/lottie_svg.js',$baseAssetsUrl . '/js/icons/onepluginfields-lottie.js']);
        }
        else{
            $jsFiles = array_merge($jsFiles,[ $baseAssetsUrl . '/js/onepluginfields-lottie.min.js']);
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$jsFile) );
        }
    }
}

