<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\records;

use craft\db\ActiveRecord;

class OnePluginFieldsOptimizedImage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%onepluginfields_optimized_image}}';
    }
}