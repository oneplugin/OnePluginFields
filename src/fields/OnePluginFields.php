<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\fields;

use Craft;

use yii\db\Schema;
use craft\base\Field;
use craft\helpers\Json;
use craft\base\ElementInterface;
use craft\web\assets\cp\CpAsset;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;
use oneplugin\onepluginfields\gql\types\OnePluginFieldGqlType;
use oneplugin\onepluginfields\OnePluginFields as OnePluginFieldsPlugin;

class OnePluginFields extends Field
{
    public $mandatory = false;
    public $allowedContents = '*';
    public $allowedSources = '*';

    
    public static function displayName(): string
    {
        return Craft::t('one-plugin-fields', 'OnePlugin Field');
    }

    public function rules(): array
    {
        $rules = parent::rules();
        return $rules;
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    public function getElementValidationRules(): array
    {
        if( $this->mandatory){
            return [
                ['required']
            ];
        }
        else{
            return [];
        }
    }
    
    public function normalizeValue($value, ElementInterface $element = null): mixed
    {
        if( $value ==  null)
        {
            return null;
        }
        if ($value instanceof OnePluginFieldsAsset)
        {
            return $value;
        }
        
        if (is_array($value) && empty($value))
        {
            return null;
        }
        
        // quick array transform so that we can ensure and `required fields` fire an error
        $valueData = (array)json_decode($value);
        // if we have actual data return model
        if (count($valueData) > 0)
        {
            return new OnePluginFieldsAsset($value);
        }
        else{
            return null;
        }
        return $value;
    }

    public function serializeValue($value, ElementInterface $element = null): mixed
    {

        if ($value instanceof OnePluginFieldsAsset)
        {
            $value = $value->json;
        }
        return parent::serializeValue($value, $element);
    }

    public function getSettingsHtml():string
    {  
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-fields/_components/fields/_settings',
            [
                'field' => $this,
                'availableContents' => $this->availableContent(),
                'availableSources' => $this->availableSources()
            ]
        );
    }

    
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $settings = OnePluginFieldsPlugin::$plugin->getSettings();
        
        $folder = 'dist';
        if( OnePluginFieldsPlugin::$devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/' . $folder,
            true
        );
        $cssFiles = [];
        $jsFiles = [];

        if( OnePluginFieldsPlugin::$devMode ){
            $cssFiles = [$baseAssetsUrl . '/css/onepluginfields.css',$baseAssetsUrl . '/themes/default/style.css'];
            $jsFiles = [ $baseAssetsUrl . '/js/icons/lottie_svg.js',$baseAssetsUrl . '/js/icons/onepluginfields-lottie.js',$baseAssetsUrl . '/js/onepluginfields.js',$baseAssetsUrl . '/js/spectrum.min.js',$baseAssetsUrl . '/js/jstree.js',$baseAssetsUrl . '/js/selectric.min.js'];
        }
        else{
            $cssFiles = [$baseAssetsUrl . '/css/onepluginfields.min.css',$baseAssetsUrl . '/themes/default/style.min.css'];
            $jsFiles = [$baseAssetsUrl . '/js/onepluginfields-cp.min.js'];
        }
        
        $dynamicMaps =  false;
        
        foreach ($cssFiles as $cssFile) {
            Craft::$app->getView()->registerCssFile($cssFile);
        }
        if( !empty($settings->mapsAPIKey) ){
            $dynamicMaps = true;
            Craft::$app->getView()->registerJsFile('https://maps.googleapis.com/maps/api/js?key=' . $settings->mapsAPIKey . '&libraries=places&v=3.exp',['depends' => CpAsset::class]);
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['depends' => CpAsset::class]);
        }
        

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $allowedContents = is_array($this->allowedContents) ? $this->allowedContents : [$this->allowedContents ];

        $allowedSources = is_array($this->allowedSources) ? $this->allowedSources : [$this->allowedSources ];
        if( sizeof( $allowedSources ) == 1 && ( empty($allowedSources[0]) || $allowedSources[0] == '*' ) ){
            $allowedSources = '*';
        }
        $jsonVars = [
            'namespace' => $namespacedId,
            //'volumes' => implode(',',$this->getAllVolumes()),
            //'folders' => implode(',',$this->getAllFolders()),
            'primary-color' => $settings->primaryColor,
            'secondary-color' => $settings->secondaryColor,
            'stroke-width' => $settings->strokeWidth,
            'svg-stroke-color' => $settings->svgStrokeColor,
            'svg-stroke-width' => $settings->svgStrokeWidth,
            'allowedSources' => $allowedSources,
            'dynamicMaps' => $dynamicMaps
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("new OnePluginFieldsSelectInput(" . $jsonVars . ");");

        // Render the input template
        $asset = null;
        if( $value != null && ( $value->iconData['type'] == 'imageAsset' || $value->iconData['type'] == 'pdf' || $value->iconData['type'] == 'office') ){
            if( isset($value->iconData['id']) && !empty($value->iconData['id'])){
                $asset = Craft::$app->getAssets()->getAssetById($value->iconData['id']);
            }
        }
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-fields/_components/fields/_input',
            [
                'name' => $this->handle,
                'fieldValue' => $value,
                'field' => $this,
                'id' => $id,
                'settings' => $settings,
                'allowedContents' => $allowedContents,
                'allowedSources' => $allowedSources,
                'asset' => $asset
            ]
        );
    }

    public function getContentGqlType(): \GraphQL\Type\Definition\Type|array
    {
        $typeArray = OnePluginFieldGqlType::generateTypes($this);

        return [
            'name' => $this->handle,
            'description' => "OnepluginField field",
            'type' => array_shift($typeArray),
        ];
    }

    private function availableContent(): array{

        return [['label' => 'All','value' =>'*'], 
                ['label' => 'Images','value' =>'imageAsset'],
                ['label' => 'Videos','value' =>'video'],
                ['label' => 'Social Media Content','value' =>'social'],
                ['label' => 'Websites','value' =>'website'],
                ['label' => 'Google Maps','value' =>'map'],
                ['label' => 'Animated Icons','value' =>'animatedIcons'],
                ['label' => 'SVG Icons','value' =>'svg'],
                ['label' => 'PDF Documents','value' =>'pdf'],
                ['label' => 'Office Documents','value' =>'office']];
    }

    private function availableSources(): array{

        $sources = Craft::$app->getElementSources()->getSources('craft\elements\Asset', 'modal');
        $options = [];
        $optionNames = [];
        foreach ($sources as $source) {
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];
                $optionNames[] = $source['label'];
            }
        }
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);
        return $options;
    }
}
