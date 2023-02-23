<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\gql\models;

use craft\base\Model;
use craft\gql\TypeLoader;
use craft\gql\base\GqlTypeTrait;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use oneplugin\onepluginfields\gql\resolvers\OnePluginFieldResolver;

class SVGIconGql extends Model
{
    use GqlTypeTrait;

    public $iconData = null;

    public static function getName($context = null): string
    {
        return 'OnePluginFields_SVGIcon';
    }

    static public function getType(): Type
    {
      $typeName = self::getName();
      $type = GqlEntityRegistry::getEntity($typeName)
        ?: GqlEntityRegistry::createEntity($typeName, new OnePluginFieldResolver([
        'name'   => static::getName(),
        'fields' => self::class . '::getFieldDefinitions',
        'description' => 'The interface implemented by OnePlugin Field SVG type.',
        ]));

        TypeLoader::registerType(static::getName(), function () use ($type) {
          return $type;
        });
      
      return $type;
    }

    /**
     * @return array
     */
    public static function getFieldDefinitions(): array {
      return [
        'iconName' => [
          'name' => 'iconName',
          'type' => Type::string(),
        ],
        'strokeColor' => [
          'name' => 'strokeColor',
          'type' => Type::string(),
        ],
        'strokeWidth' => [
          'name' => 'strokeWidth',
          'type' => Type::float(),
        ],
        'icon' => [
          'name' => 'icon',
          'type' => Type::string(),
        ]
      ];
    }

    public function __construct($value)
    {
        if( $value != null){
            $this->iconData = (array)json_decode($value,true);
        }
        else{
            $this->iconData = [];
        }
    }

    public function getIconName() {
        return $this->iconData['asset']['icon-name'];
    }

    public function getStrokeColor() {
        return $this->iconData['asset']['icon-primary'];
    }

    public function getStrokeWidth() {
      return is_null($this->iconData['asset']['icon-stroke-width'])? 1.0: floatval($this->iconData['asset']['icon-stroke-width']);
    }

    public function getIcon() {
      return $this->iconData['asset']['svg-data'];
    }
    
}