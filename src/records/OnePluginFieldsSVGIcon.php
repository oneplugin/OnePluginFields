<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\records;

use craft\db\ActiveRecord;

class OnePluginFieldsSVGIcon extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%onepluginfields_svg_icon}}';
    }
}