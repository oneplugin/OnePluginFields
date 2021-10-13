<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\fields;

use oneplugin\onepluginfields\OnePluginFields as OnePluginFieldsPlugin;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;
use craft\errors\InvalidSubpathException;
use craft\errors\InvalidVolumeException;
use craft\fields\Assets;
use craft\helpers\Html;
use craft\web\assets\cp\CpAsset;
use oneplugin\animatedicons\models\AnimatedIconData;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

/**
 * OnePluginFieldsField Field
 *
 * Whenever someone creates a new field in Craft, they must specify what
 * type of field it is. The system comes with a handful of field types baked in,
 * and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 *
 * @author    Jagadeesh Vijayakumar
 * @package   OnePluginFields
 * @since     1.0.0
 */
class OnePluginFields extends Field
{
    // Public Properties
    // =========================================================================

    public $mandatory = false;

    /** @var array */
    public $allowedContents = '*';

    const DEV_MODE = false;

    // Static Methods
    // =========================================================================

    
    public static function displayName(): string
    {
        return Craft::t('one-plugin-fields', 'OnePlugin Field');
    }

    // Public Methods
    // =========================================================================

    public function rules()
    {
        $rules = parent::rules();
        //$rules[] = [['mandatory'], 'required'];
        //$rules[] = [['defaultWidth', 'defaultHeight'], 'number'];
        return $rules;
    }

    /**
     * Returns the column type that this field should get within the content table.
     *
     * This method will only be called if [[hasContentColumn()]] returns true.
     *
     * @return string The column type. [[\yii\db\QueryBuilder::getColumnType()]] will be called
     * to convert the give column type to the physical one. For example, `string` will be converted
     * as `varchar(255)` and `string(100)` becomes `varchar(100)`. `not null` will automatically be
     * appended as well.
     * @see \yii\db\QueryBuilder::getColumnType()
     */
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
    
    /**
     * Normalizes the field’s value for use.
     *
     * This method is called when the field’s value is first accessed from the element. For example, the first time
     * `entry.myFieldHandle` is called from a template, or right before [[getInputHtml()]] is called. Whatever
     * this method returns is what `entry.myFieldHandle` will likewise return, and what [[getInputHtml()]]’s and
     * [[serializeValue()]]’s $value arguments will be set to.
     *
     * @param mixed                 $value   The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     *
     * @return mixed The prepared field value
     */
    public function normalizeValue($value, ElementInterface $element = null)
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

    /**
     * Prepares the field’s value to be stored somewhere, like the content table or JSON-encoded in an entry revision table.
     *
     * Data types that are JSON-encodable are safe (arrays, integers, strings, booleans, etc).
     * Whatever this returns should be something [[normalizeValue()]] can handle.
     *
     * @param mixed $value The raw field value
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @return mixed The serialized field value
     */
    public function serializeValue($value, ElementInterface $element = null)
    {

        if ($value instanceof OnePluginFieldsAsset)
        {
            $value = $value->json;
        }
        return parent::serializeValue($value, $element);
    }

    public function getSettingsHtml()
    {  
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-fields/_components/fields/OnePlugiFields_settings',
            [
                'field' => $this,
                'availableContents' => $this->availableContent()
            ]
        );
    }

    
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $settings = OnePluginFieldsPlugin::$plugin->getSettings();
        
        $folder = 'dist';
        if( self::DEV_MODE ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/' . $folder,
            true
        );
        $cssFiles = [];
        $jsFiles = [];

        if( self::DEV_MODE ){
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
        $jsonVars = [
            'namespace' => $namespacedId,
            'volumes' => implode(',',$this->getAllVolumes()),
            'folders' => implode(',',$this->getAllFolders()),
            'primary-color' => $settings->primaryColor,
            'secondary-color' => $settings->secondaryColor,
            'svg-stroke-color' => $settings->svgStrokeColor,
            'dynamicMaps' => $dynamicMaps
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("new OnePluginFieldsSelectInput(" . $jsonVars . ");");

        $allowedContents = is_array($this->allowedContents) ? $this->allowedContents : [$this->allowedContents ];
        // Render the input template
        $asset = null;
        if( $value != null && ( $value->iconData['type'] == 'imageAsset' || $value->iconData['type'] == 'pdf' || $value->iconData['type'] == 'office') ){
            if( isset($value->iconData['id']) && !empty($value->iconData['id'])){
                $asset = Craft::$app->getAssets()->getAssetById($value->iconData['id']);
            }
        }
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-fields/_components/fields/OnePluginFields_input',
            [
                'name' => $this->handle,
                'fieldValue' => $value,
                'field' => $this,
                'id' => $id,
                'settings' => $settings,
                'allowedContents' => $allowedContents,
                'asset' => $asset
            ]
        );
    }

    private function getAllVolumes(){
        $allVolumes = [];
        $volumes = Craft::$app->getVolumes();
        $publicVolumes = $volumes->getPublicVolumes();
        foreach ($publicVolumes as $volume){
            $allVolumes[] = 'volumes:' . $volume->uid;
        }

        return $allVolumes;
    }

    private function getAllFolders(){
        $allVolumes = [];
        $volumes = Craft::$app->getVolumes();
        $publicVolumes = $volumes->getPublicVolumes();
        foreach ($publicVolumes as $volume){
            $allVolumes[] = 'folder:' . $this->_volumeSourceToFolderSource($volume->uid);
        }

        return $allVolumes;
    }

    private function _volumeSourceToFolderSource($sourceKey): string
    {
        if ($sourceKey && is_string($sourceKey) && strpos($sourceKey, 'volume:') === 0) {
            $parts = explode(':', $sourceKey);
            $volume = Craft::$app->getVolumes()->getVolumeByUid($parts[1]);

            if ($volume && $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)) {
                return 'folder:' . $folder->uid;
            }
        }

        return (string)$sourceKey;
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
}
