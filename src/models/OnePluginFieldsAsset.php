<?php


namespace oneplugin\onepluginfields\models;

use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\helpers\Template as TemplateHelper;
use oneplugin\onepluginfields\services\OnePluginFieldsService;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\records\OnePluginFieldsOptimizedImage as OnePluginFieldsOptimizedImageRecord;
use oneplugin\onepluginfields\render\RenderInterface;
use oneplugin\onepluginfields\render\BaseRenderer;
use oneplugin\onepluginfields\render\ImageRenderer;
use oneplugin\onepluginfields\render\RichContentRenderer;
use oneplugin\onepluginfields\render\GoogleMapsRenderer;
use oneplugin\onepluginfields\render\AnimatedIconRenderer;
use oneplugin\onepluginfields\render\SVGIconRenderer;
use oneplugin\onepluginfields\render\PDFRenderer;
use oneplugin\onepluginfields\render\OfficeRenderer;
use DOMDocument;
use DOMXPath;
use Craft;
use craft\elements\Asset;
use craft\helpers\Component as ComponentHelper;
use ether\seo\models\Settings;
use ether\seo\Seo;
use oneplugin\onepluginfields\models\OnePluginFieldsOptimizedImage as OnePluginFieldsOptimizedImageModel;
use yii\base\BaseObject;

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
        if( $this->iconData && ($this->iconData['type'] == 'imageAsset' || $this->iconData['type'] == 'video' || $this->iconData['type'] == 'social' || $this->iconData['type'] == 'website' || $this->iconData['type'] == 'office' || $this->iconData['type'] == 'pdf') )
            return $this->iconData['asset'];

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

    private function getSrcset(OnePluginFieldsOptimizedImageModel $optimizedImage): string
    {
        $srcset = '';
        foreach ($optimizedImage->imageUrls as $key => $value) {
            if( !empty($value['url']) ){
                $srcset .= $value['url'] . ' ' . $key . 'w, ';
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
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