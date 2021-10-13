<?php

/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */


namespace oneplugin\onepluginfields\models;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\console\Application as ConsoleApplication;
use craft\db\Paginator;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\helpers\App;
use craft\models\AssetTransform;
use craft\helpers\Image;

class OnePluginFieldsOptimizedImage{

    public $name;

    public $extension;

    public $width;

    public $height;

    public $originalUrl = '';

    public $imageUrls = [];
    
    public $fallbackImageUrls = [];

    public $placeHolder = '';

    public $errors = [];

    public function __construct($value)
    {
        if($this->validateJson($value)){
            $json = (array)json_decode($value,true);
            $this->name = $json['name'];
            $this->extension = $json['extension'];
            $this->width = $json['width'];
            $this->height = $json['height'];
            $this->originalUrl = $json['originalUrl'];
            $this->placeHolder = $json['placeHolder'];
        	$this->imageUrls = $json['imageUrls'];
            if( isset($json['fallbackImageUrls'])){
                $this->fallbackImageUrls = $json['fallbackImageUrls'];
            }
        } else {

        }
    }

    private function validateJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }

}