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
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

class SVGIconRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $plugin = OnePluginFields::$plugin;
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        $svg = null;
        try{
            $doc->loadXML($asset->iconData['asset']['svg-data']);
            if( $doc->getElementsByTagName('svg') && $doc->getElementsByTagName('svg')->length > 0){
                $svg = $doc->getElementsByTagName('svg')->item(0);
                if( $svg->getAttribute('width'))
                    $svg->removeAttribute('width');
                if( $svg->getAttribute('height'))
                    $svg->removeAttribute('height');
            }
            else{
                $svg = $doc->createElement('svg');
            }
            empty($attributes['class']) ?:$this->setAttribute($doc,$svg,'class',$attributes['class']);
            if( $attributes['size'] ){
                $this->setAttribute($doc,$svg,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            return [$this->htmlFromDOMAfterAddingProperties($doc,$svg,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
}