<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\models;

use Craft;
use craft\helpers\UrlHelper;
use craft\helpers\Template as TemplateHelper;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\render\PDFRenderer;
use oneplugin\onepluginfields\gql\models\ImageGql;
use oneplugin\onepluginfields\render\BaseRenderer;
use oneplugin\onepluginfields\render\ImageRenderer;
use oneplugin\onepluginfields\gql\models\SVGIconGql;
use oneplugin\onepluginfields\render\OfficeRenderer;
use oneplugin\onepluginfields\render\RenderInterface;
use oneplugin\onepluginfields\render\SVGIconRenderer;
use oneplugin\onepluginfields\render\GoogleMapsRenderer;
use oneplugin\onepluginfields\gql\models\AnimatedIconGql;
use oneplugin\onepluginfields\render\RichContentRenderer;
use oneplugin\onepluginfields\render\AnimatedIconRenderer;

class OnePluginFieldsAsset
{
    private $defaultSize = ["animatedIcons" => ["width" => "256px","height" => "256px"],"svg" => ["width" => "256px","height" => "256px"],"imageAsset" => ["width" => "100%","height" => "100%"],"video" => ["width" => "100%","height" => "100%"],"social" => ["width" => "100%","height" => "100%"]
                                ,"website" => ["width" => "100%","height" => "100%"],"pdf" => ["width" => "100%","height" => "100%"],"office" => ["width" => "100%","height" => "100%"],"gmap" => ["width" => "100%","height" => "100%"]];
    private $renderers = ["imageAsset" => ["classname" => 'oneplugin\onepluginfields\render\ImageRenderer', "class" => ImageRenderer::class],
                          "video" => ["classname" => 'oneplugin\onepluginfields\render\RichContentRenderer', "class" => RichContentRenderer::class],
                          "social" => ["classname" => 'oneplugin\onepluginfields\render\RichContentRenderer', "class" => RichContentRenderer::class],
                          "website" => ["classname" => 'oneplugin\onepluginfields\render\RichContentRenderer', "class" => RichContentRenderer::class],
                          "gmap"  => ["classname" => 'oneplugin\onepluginfields\render\GoogleMapsRenderer', "class" => GoogleMapsRenderer::class],
                          "animatedIcons"  => ["classname" => 'oneplugin\onepluginfields\render\AnimatedIconRenderer', "class" => AnimatedIconRenderer::class],
                          "svg"  => ["classname" => 'oneplugin\onepluginfields\render\SVGIconRenderer', "class" => SVGIconRenderer::class],
                          "pdf"  => ["classname" => 'oneplugin\onepluginfields\render\PDFRenderer', "class" => PDFRenderer::class],
                          "office"  => ["classname" => 'oneplugin\onepluginfields\render\OfficeRenderer', "class" => OfficeRenderer::class]
                            ]; 
	public $output = '';
    public $json = '';
    public $iconData = null;

    public function __construct($value)
    {
        if($this->validateJson($value)){
        	$this->json = $value;
        	$this->iconData = (array)json_decode($value,true);
        } else {
            $value = null;
            $this->iconData = null;
        }
    }

    public function __toString()
    {
        return $this->output;
    }

    public function url()
    {
        if( $this->iconData && ( $this->iconData['type'] == 'video' || $this->iconData['type'] == 'social' || $this->iconData['type'] == 'website') ){
            return $this->iconData['asset'];
        }
        else if( $this->iconData && ($this->iconData['type'] == 'imageAsset' || $this->iconData['type'] == 'office' || $this->iconData['type'] == 'pdf')) {
            $asset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
            if( $asset ){
                return $asset->getUrl();
            }
        }
        return "";
    }

    public function type()
    {
        if( $this->iconData )
            return $this->iconData['type'];
        return "";
    }

    public function render(array $options = [])
    {
        $settings = OnePluginFields::$plugin->getSettings();
        $hash = 'op_' . $settings->opSettingsHash . '_' . $settings->opImageTag . '_' . $settings->aIconDataAsHtml . md5($this->json . json_encode($options));
        if( $settings->enableCache && Craft::$app->cache->exists($hash)) {
            $renderer = $this->createAssetRenderer();
            $renderer->includeAssets();
            return TemplateHelper::raw(\Craft::$app->cache->get($hash));
        }
        $cache = true;
        $renderer = $this->createAssetRenderer();
        if( $renderer != null){
            list($html,$cache) = $renderer->render($this,$options);
            if( $settings->enableCache && $cache ){
                Craft::$app->cache->set($hash, $html,86400);
            }
            return TemplateHelper::raw($html);;
        }
        return TemplateHelper::raw('<div>Renderer Exception </div>');
    }

    public function getThumbHtml(){
        
        if( $this->iconData )
        {
            if( ($this->iconData['type'] == 'imageAsset' || $this->iconData['type'] == 'pdf' || $this->iconData['type'] == 'office') && isset($this->iconData['id']) && $this->iconData['id'] != null){
                $asset = Craft::$app->getAssets()->getAssetById((int) $this->iconData['id']);        
                if( $asset )
                {
                    return TemplateHelper::raw( $asset->getPreviewThumbImg(34,34) );
                }
            }
        }
        return '';
    }

    public function getName() {
        return 'OnepluginFields';
    }

    public function getType() {
        return $this->iconData['type'];
    }

    public function getJsAssets() {
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/dist',
            true
        );
        $jsFiles = [];

        if( $this->iconData['type'] == 'pdf'){
            $jsFiles[] = $baseAssetsUrl . '/js/pdf.min.js';
        }
        else if( $this->iconData['type'] == 'animatedIcons'){
            $jsFiles[] = $baseAssetsUrl . '/js/onepluginfields-lottie.min.js';
        }
        else if( $this->iconData['type'] == 'gmap'){
            if( !empty($this->settings->mapsAPIKey)){
                $jsFiles[] = 'https://maps.googleapis.com/maps/api/js?key=' . $settings->mapsAPIKey . '&libraries=places&v=3.exp';
                $jsFiles[] = $baseAssetsUrl . '/js/map.min.js';
            }
        }
        return $jsFiles;
    }
    
    public function getTag($args) {
        $opts = $args['options'] ?? [];
        $options = array();
        foreach ($opts as $opt) {
            foreach ($opt as $key => $value) {
                $options[$key] = $value;
            }
        }
        return $this->render($options);
    }

    public function getImage() {
        if( $this->iconData['type'] == 'imageAsset'){
            //$imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
            //return $imageAsset;
            return new ImageGql($this->json);
        }
        return null;
    }

    public function getAnimatedIcon() {
        if( $this->iconData['type'] == 'animatedIcons'){
            return new AnimatedIconGql($this->json);
        }
        return null;
    }

    public function getSvgIcon() {
        if( $this->iconData['type'] == 'svg'){
            return new SVGIconGql($this->json);
        }
        return null;
    }

    public function getSrc()
    {
        switch( (string)$this->iconData['type'] ){
            case 'imageAsset':
                $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
                if( $imageAsset ){
                    return $imageAsset->getUrl();
                }
                break;
            case 'video':
            case 'social':
            case 'website':
            case 'pdf':
            case 'office':
                return $this->iconData['asset'];
            case 'gmap':
                return '';
            case 'animatedIcons':
                $url = UrlHelper::actionUrl('one-plugin-fields/one-plugin/load/',[ 'name' => $this->iconData['asset']['icon-name'],'type' => 'aicon','trigger'=>$this->iconData['asset']['icon-trigger'] ] );
                return $url;
            case 'svg':
                return '';
            }
        
    }

    private function createAssetRenderer(): RenderInterface
    {
        /** @var RenderInterface $renderer */
        $renderer = null;
        try {
            if( isset( $this->renderers[$this->iconData['type']] ) ){
                $renderer = Craft::createObject($this->renderers[$this->iconData['type']]["classname"]);
                if( $renderer instanceof $this->renderers[$this->iconData['type']]["class"]) {
                    return $renderer;
                }
            }
            $renderer = new BaseRenderer();
        } catch (\Throwable $e) {
            $renderer = new BaseRenderer();
            Craft::error($e->getMessage(), __METHOD__);
        }
        return $renderer;
    }

    private function normalizeOptions(array $options){

        if (empty($options['width'])){
            $options['width'] = $this->defaultSize[$this->iconData['type']]['width'];
        }
        if (empty($options['height'])){
            $options['height'] = $this->defaultSize[$this->iconData['type']]['height'];
        }

        return $options;
    }
    
    private function setAttribute($doc, $elem, $name, $value){
        
        $attribute = $doc->createAttribute($name);
        $attribute->value = htmlspecialchars($value);
        $elem->appendChild($attribute);
    }
    private function validateJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }
}