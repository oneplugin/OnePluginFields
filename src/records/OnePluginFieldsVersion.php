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

class OnePluginFieldsVersion extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%onepluginfields_config}}';
    }

    public static function latest_version()
    {
        $version = OnePluginFieldsVersion::find()
                ->where(['id' => 1])->limit(1)
                ->all();
        if (count($version) > 0 ) {
            return $version[0]['content_version_number'];
        }
        else{
            return '1.0';
        }
    }
}