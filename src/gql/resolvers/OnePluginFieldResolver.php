<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\gql\resolvers;

use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

class OnePluginFieldResolver extends ObjectType
{
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;
        return $source->{'get' . ucfirst($fieldName)}(empty($arguments) ? false : $arguments);
    }
}