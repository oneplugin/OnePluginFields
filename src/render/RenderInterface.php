<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\render;

use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

interface RenderInterface
{
    /**
     * Return an HTML string for the corresponding content type
     *
     * @param OnePluginFieldsAsset              $asset
     * @param array               $options
     *
     * @return string
     */
    public function render(OnePluginFieldsAsset $asset, array $options): array;
}