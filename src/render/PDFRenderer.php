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

class PDFRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $html = '';
        $fieldId = $asset->iconData['id'];
        $fieldId .= '-' . $this->generateRandomString(); //required to make PDF's work if the same field is used multiple times on the same page. However if Cache is enabled, this will fix will not work. So added a fix in JS.
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        if( !empty($attributes['navigation-bar']) && $attributes['navigation-bar'] != 'below' ){
            $attributes['navigation-bar'] = 'above';
        }
        else if( empty($attributes['navigation-bar']) ){
            $attributes['navigation-bar'] = 'above';
        }
        try{
            $width = $height = '';
            if( $attributes['size'] ){
                $width = $attributes['width'];
                $height = $attributes['height'];
                if( $attributes['width'] == '100%'){
                    $width = '4000';
                }
                if( $attributes['height'] == '100%'){
                    $height = '4000';
                }
            }
            if( empty($width) || empty($height) ){ //JS will automatically set the size based on aspect ratio
                $width = '4000';
                $height = '4000';
            }

            $div = $doc->createElement('div');
            if( empty($attributes['class'])){
                $this->setAttribute($doc,$div,'class','onepluginfields-pdf-btns');
            }
            else{
                $this->setAttribute($doc,$div,'class','onepluginfields-pdf-btns ' .$attributes['class']);
            }
            $prevbtn = $doc->createElement('button','Prev');
            $this->setAttribute($doc,$prevbtn,'class','btn previous');
            $this->setAttribute($doc,$prevbtn,'id',$fieldId . '-prev');
            $div->appendChild($prevbtn);
            $div_span = $doc->createElement('div');
            $div->appendChild($div_span);
            $this->setAttribute($doc,$div_span,'class','page-wrapper');
            $span = $doc->createElement('span');
            $this->setAttribute($doc,$span,'class','page');
            $this->setAttribute($doc,$span,'id',$fieldId . '-page-num');
            $div_span->appendChild($span);
            $span = $doc->createElement('span',' / ');
            $div_span->appendChild($span);
            $span = $doc->createElement('span');
            $this->setAttribute($doc,$span,'class','page');
            $this->setAttribute($doc,$span,'id',$fieldId . '-page-count');
            $div_span->appendChild($span);
            $nextbtn = $doc->createElement('button','Next');
            $this->setAttribute($doc,$nextbtn,'class','btn next');
            $this->setAttribute($doc,$nextbtn,'id',$fieldId . '-next');
            $div->appendChild($nextbtn);

            $canvas = $doc->createElement('canvas');
            $this->setAttribute($doc,$canvas,'width',$width);
            $this->setAttribute($doc,$canvas,'height',$height);
            if( empty($attributes['class'])){
                $this->setAttribute($doc,$canvas,'class','onepluginfields-pdfviewer');
            }
            else{
                $this->setAttribute($doc,$canvas,'class','onepluginfields-pdfviewer ' .$attributes['class']);
            }
            $this->setAttribute($doc,$canvas,'style','direction: ltr;');
            $this->setAttribute($doc,$canvas,'preserveaspect','yes');
            $this->setAttribute($doc,$canvas,'id',$fieldId);
            $this->setAttribute($doc,$canvas,'data-url',$asset->iconData['asset']);

            unset($attributes['width']);
            unset($attributes['height']);
            unset($attributes['class']);
            unset($attributes['size']);
            foreach ($attributes as $key => $value){
                $this->setAttribute($doc,$canvas,$key,$value);
            }
            if($attributes['navigation-bar'] == 'above'){
                $doc->appendChild($div);    
            }
            $doc->appendChild($canvas);
            if($attributes['navigation-bar'] == 'below'){
                $doc->appendChild($div);
            }
            $html = $doc->saveHTML();
            return [$html,true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }

    function generateRandomString($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}