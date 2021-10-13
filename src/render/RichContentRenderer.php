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
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

class RichContentRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $plugin = OnePluginFields::$plugin;
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $html = '';
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        try{
            $html = $plugin->onePluginFieldsService->videoPreviewService($asset->iconData['asset'],$attributes);
            $doc->loadHTML($html);
            $element = $doc->getElementsByTagName('iframe')->item(0);
            $element = ($element) ? $element : $doc->getElementsByTagName('div')->item(0);
            $element = ($element) ? $element : $doc->getElementsByTagName('blockquote')->item(0);
            if( $element ){
                $element->removeAttribute('allow');
                $element->removeAttribute('frameborder');
                $element->removeAttribute('allowfullscreen');
                $element->removeAttribute('style');
                if( $attributes['size'] ){
                    $this->setAttribute($doc,$element,'width',$attributes['width']);
                    $this->setAttribute($doc,$element,'height',$attributes['height']);
                }
                else{
                    $this->setAttribute($doc,$element,'width','100%');
                    $this->setAttribute($doc,$element,'height','100%');
                }
                $this->setAttribute($doc,$element,'allow','autoplay');
                if(!empty($attributes['class'])){
                    $class = $element->getAttribute('class');
                    $class = empty($class) ? $attributes['class'] : $class . ' ' . $attributes['class'];
                    $this->setAttribute($doc,$element,'class',$class);
                }
                return [$this->htmlFromDOMAfterAddingProperties($doc,$element,$attributes), true];
            }
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        return [$html,true];
    }
}