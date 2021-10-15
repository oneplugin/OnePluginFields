<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\render;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Craft;
use craft\helpers\UrlHelper;
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
        try{
            $svg = $doc->createElement('svg');
            empty($attributes['class']) ?:$this->setAttribute($doc,$svg,'class',$attributes['class']);
            if( $attributes['size'] ){
                $this->setAttribute($doc,$svg,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            $this->setAttribute($doc,$svg,'stroke-width',$asset->iconData['asset']['icon-stroke-width']);
            $this->setAttribute($doc,$svg,'stroke',$asset->iconData['asset']['icon-primary']);
            $this->setAttribute($doc,$svg,'viewbox','0 0 24 24');
            $this->setAttribute($doc,$svg,'fill','none');
            $this->setAttribute($doc,$svg,'stroke-linecap','round');
            $this->setAttribute($doc,$svg,'stroke-linejoin','round');
            $svg->appendChild($doc->createCDATASection($asset->iconData['asset']['svg-data']));
            return [$this->htmlFromDOMAfterAddingProperties($doc,$svg,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
}