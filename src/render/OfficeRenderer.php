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

use Craft;
use DOMDocument;
use craft\helpers\Html;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

class OfficeRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $attributes = $this->normalizeOptionsForSize($asset,$options);

        try{
            if( strpos(gethostname(), '.local') !== false ) { 
                return [Html::tag('div', Craft::t('one-plugin-fields', 'Embedding Office documents will work only on servers with public access urls')),false];
            }

            $url = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $asset->iconData['asset'];
            $iframe = $doc->createElement('iframe');
            if( $attributes['size'] ){
                $this->setAttribute($doc,$iframe,'width',$attributes['width']);
                $this->setAttribute($doc,$iframe,'height',$attributes['height']);
            }
            $this->setAttribute($doc,$iframe,'src',$url);
            empty($attributes['class']) ?:$this->setAttribute($doc,$iframe,'class',$attributes['class']);
            return [$this->htmlFromDOMAfterAddingProperties($doc,$iframe,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginfields');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
}