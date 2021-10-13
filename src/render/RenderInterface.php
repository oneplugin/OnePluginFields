<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
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