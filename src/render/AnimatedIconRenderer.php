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

class AnimatedIconRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $plugin = OnePluginFields::$plugin;
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $html = '';
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
            $this->setAttribute($doc,$aIcon,'src',$url);
            return [$this->htmlFromDOMAfterAddingProperties($doc,$aIcon,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
}