<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\models;

use oneplugin\onepluginfields\OnePluginFields;

use Craft;
use craft\base\Model;

/**
 * OnePluginFields Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Jagadeesh Vijayakumar
 * @package   OnePluginFields
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $pluginName = 'OnePlugin Fields';
    public $primaryColor = '#545454';
    public $secondaryColor = '#66a1ee';
    public $svgStrokeColor = '#66a1ee';
    public $opOutputFormat = 'webp';
    public $opImageVariants = [
            [
            "opWidth" => "1600",
            "opQuality" => "90"
            ],
            [
                "opWidth" => "1200",
                "opQuality" => "90"
            ],
            [
                "opWidth" => "992",
                "opQuality" => "85"
            ],
            [
                "opWidth" => "768",
                "opQuality" => "80"
            ],
            [
                "opWidth" => "576",
                "opQuality" => "75"
            ],
    ];
    public $opUpscale = false;

    public $opImageTag = 'picture';
    
    public $mapsAPIKey = '';

    public $enableCache = true;

    public $aIconDataAsHtml = true;

    public $newContentPackAvailable = false;

    public $opSettingsHash = 'f9b3ab9dab8d9967db789dec586cafa6';

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'primaryColor', 'secondaryColor','svgStrokeColor'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];

        return $rules;
    }
}
