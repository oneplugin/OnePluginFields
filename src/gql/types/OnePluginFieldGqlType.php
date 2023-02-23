<?php

namespace oneplugin\onepluginfields\gql\types;

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use craft\gql\base\GeneratorInterface;
use GraphQL\Type\Definition\InputObjectType;
use oneplugin\onepluginfields\gql\models\ImageGql;
use oneplugin\onepluginfields\gql\models\SVGIconGql;
use oneplugin\onepluginfields\gql\models\AnimatedIconGql;
use oneplugin\onepluginfields\gql\resolvers\OnePluginFieldResolver;

class OnePluginFieldGqlType implements GeneratorInterface
{
  /**
   * @return string
   */
    public static function getName($context = null): string
    {
      return 'OnepluginField_Field';
    }

  /**
   * @return Type
   */
  public static function generateTypes($context = null): array{

    $tagArgument = GqlEntityRegistry::getEntity("OnePluginFields_TagArgument") ?: GqlEntityRegistry::createEntity("OnePluginFields_TagArgument", new InputObjectType([
        'name' => 'Tag Argument',
        'fields' => [
          'class' => [
            'name' => 'class',
            'type' => Type::string(),
          ],
          'size' => [
            'name' => 'size',
            'type' => Type::boolean(),
          ],
          'width' => [
            'name' => 'width',
            'type' => Type::string(),
          ],
          'height' => [
            'name' => 'height',
            'type' => Type::string(),
          ],
          'alt' => [
            'name' => 'alt',
            'type' => Type::string(),
          ],
          'navigationbar' => [
            'name' => 'navigationbar',
            'type' => Type::string(),
          ]
        ]]));

    $typeName = self::getName($context);

    $onePluginFields = [
      'name' => [
        'name' => 'name',
        'type' => Type::string(),
      ],
      'type' => [
        'name' => 'type',
        'type' => Type::string(),
      ],
      'jsAssets' => [
        'name' => 'jsAssets',
        'type' => Type::listOf(Type::string()),
      ],
      'tag' => [
        'name' => 'tag',
        'type' => Type::string(),
        'args' => [
          'options' => [
              'name' => 'options',
              'type' => Type::listOf($tagArgument),
              'description' => 'If true, returns webp images.'
          ],
        ],
        'description' => 'A `<oneplugin>` tag based on this asset.',
      ],
      'src' => [
        'name' => 'src',
        'type' => Type::string(),
        'description' => 'Returns a `src` attribute value',
      ],
      'image' => [
        'name' => 'image',
        'type' => ImageGql::getType(),
      ],
      'animatedIcon' => [
        'name' => 'animatedIcon',
        'type' => AnimatedIconGql::getType(),
      ],
      'svgIcon' => [
        'name' => 'svgIcon',
        'type' => SVGIconGql::getType(),
      ]
    ];

    $type = GqlEntityRegistry::getEntity($typeName)
        ?: GqlEntityRegistry::createEntity($typeName, new OnePluginFieldResolver([
          'name'   => static::getName(),
          'fields' => function () use ($onePluginFields) {
            return $onePluginFields;
          },
          'description' => 'This is the interface implemented by OnePlugin Fields.',
        ]));

    TypeLoader::registerType($typeName, function () use ($type) {
        return $type;
    });

    return [$type];
  }
}