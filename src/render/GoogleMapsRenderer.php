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
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

class GoogleMapsRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $plugin = OnePluginFields::$plugin;
        $settings = $plugin->getSettings();
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $html = '';
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        $element = null;
        try{
            $message = 'data-map-message=""';
            if( !empty($attributes['static']) && $attributes['static'] && !empty($settings->mapsAPIKey) && !empty($asset->iconData['asset']['latitude']) && !empty($asset->iconData['asset']['longitude']) ){
                $image = $doc->createElement('image');
                $width = $height = '';
                if( $attributes['size'] ){
                    $this->setAttribute($doc,$image,'width',$attributes['width']);
                    $this->setAttribute($doc,$image,'height',$attributes['height']);
                    $width = str_replace('%','',str_replace('px','',$attributes['width']));
                    $height = str_replace('%','',str_replace('px','',$attributes['height']));
                }
                else{
                    $attributes = $this->normalizeOptions($attributes);
                    $width = str_replace('%','',str_replace('px','',$attributes['width']));
                    $height = str_replace('%','',str_replace('px','',$attributes['height']));
                }
                
                $url = 'https://maps.googleapis.com/maps/api/staticmap?size=' . $width . 'x' . $height .'&visible=' . $asset->iconData['asset']['latitude'] . ',' . $asset->iconData['asset']['longitude'] . '&markers=color:red|' . $asset->iconData['asset']['latitude'] . ',' . $asset->iconData['asset']['longitude'];
                $this->setAttribute($doc,$image,'src',$url);
                empty($attributes['class']) ?:$this->setAttribute($doc,$image,'class',$attributes['class']);
                $element = $image;
            }
            else if( !empty($asset->iconData['asset']['place']) ){
                $url = 'https://maps.google.com/maps?width=100%&height=100%&hl=en&q=' . $asset->iconData['asset']['place'] . '&z=14&output=embed';
                $iframe = $doc->createElement('iframe');
                if( $attributes['size'] ){
                    $this->setAttribute($doc,$iframe,'width',$attributes['width']);
                    $this->setAttribute($doc,$iframe,'height',$attributes['height']);
                }
                $this->setAttribute($doc,$iframe,'src',$url);
                empty($attributes['class']) ?:$this->setAttribute($doc,$iframe,'class',$attributes['class']);
                $element = $iframe;
            }
            else{
                $div = $doc->createElement('div');
                if( $attributes['size'] ){
                    $this->setAttribute($doc,$div,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
                }
                $this->setAttribute($doc,$div,'data-map-latitude',$asset->iconData['asset']['latitude']);
                $this->setAttribute($doc,$div,'data-map-longitude',$asset->iconData['asset']['longitude']);
                empty($attributes['class']) ? $this->setAttribute($doc,$div,'class','onepluginfields-gmap'):$this->setAttribute($doc,$div,'class','onepluginfields-gmap ' . $attributes['class']);
                $element = $div;
            }
            $this->includeAssets();
            return [$this->htmlFromDOMAfterAddingProperties($doc,$element,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }

    public function includeAssets()
    {
        $settings = OnePluginFields::$plugin->getSettings();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/dist/',
            true
        );

        $jsFiles = [];
        if( !empty($settings->mapsAPIKey) ){
            $jsFiles[] = 'https://maps.googleapis.com/maps/api/js?key=' . $settings->mapsAPIKey . '&libraries=places&v=3.exp';
            $jsFiles[] = $baseAssetsUrl . '/js/map/map.js';
        }

        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$jsFile) );
        }
    }
}