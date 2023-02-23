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

use craft\base\Model;

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
    public $strokeWidth = 50;
    public $svgStrokeColor = '#66a1ee';
    public $svgStrokeWidth = 50;
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
    public function rules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'primaryColor', 'secondaryColor','svgStrokeColor','strokeWidth','svgStrokeWidth'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['strokeWidth','svgStrokeWidth'], 'number', 'integerOnly' => true];
        $rules[] = [['strokeWidth','svgStrokeWidth'], 'number', 'min' => 1];
        $rules[] = [['strokeWidth','svgStrokeWidth'], 'number', 'max' => 100];

        return $rules;
    }
}
