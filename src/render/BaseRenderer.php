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
use Craft;
use craft\helpers\Html;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

class BaseRenderer implements RenderInterface
{
    private $defaultSize = ["animatedIcons" => ["width" => "256px","height" => "256px"],"svg" => ["width" => "256px","height" => "256px"],"imageAsset" => ["width" => "100%","height" => "100%"],"video" => ["width" => "100%","height" => "100%"],"social" => ["width" => "100%","height" => "100%"]
                                ,"website" => ["width" => "100%","height" => "100%"],"pdf" => ["width" => "100%","height" => "100%"],"office" => ["width" => "100%","height" => "100%"],"gmap" => ["width" => "100%","height" => "100%"]];

    public function render(OnePluginFieldsAsset $asset, array $options): array{

        return [Html::tag('div', Craft::t('one-plugin-fields', 'No renderer found for type ' . $asset->iconData['type'])),false];
    }

    protected function normalizeOptionsForSize(OnePluginFieldsAsset $asset,array $options){

        $options['size'] = empty($options['size']) ? false : $options['size'];
        if( $options['size'] ){
            if (empty($options['width'])){
                $options['width'] = $this->defaultSize[$asset->asset['type']]['width'];
            }
            if (empty($options['height'])){
                $options['height'] = $this->defaultSize[$asset->iconData['type']]['height'];
            }    
        }
        return $options;
    }
    protected function setAttribute($doc, $elem, $name, $value){
        
        $attribute = $doc->createAttribute($name);
        $attribute->value = htmlspecialchars($value);
        $elem->appendChild($attribute);
    }

    protected function htmlFromDOMAfterAddingProperties(DOMDocument $doc, DOMElement $element, array $attributes ):string{
        if( $element){
            unset($attributes['width']);
            unset($attributes['height']);
            unset($attributes['class']);
            unset($attributes['size']);
            foreach ($attributes as $key => $value){
                $this->setAttribute($doc,$element,$key,$value);
            }
            $doc->appendChild($element);
        }
        $html = $doc->saveHTML();
        return $html;
    }

    protected function htmlFromDOM(DOMDocument $doc, DOMElement $element):string{
        $doc->appendChild($element);
        $html = $doc->saveHTML();
        return $html;
    }
}