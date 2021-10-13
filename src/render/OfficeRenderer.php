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

use DOMDocument;
use DOMElement;
use DOMXPath;
use Craft;
use craft\helpers\UrlHelper;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\models\OnePluginFieldsAsset;

class OfficeRenderer extends BaseRenderer
{
    public function render(OnePluginFieldsAsset $asset, array $options): array{
        
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $attributes = $this->normalizeOptionsForSize($asset,$options);

        try{
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