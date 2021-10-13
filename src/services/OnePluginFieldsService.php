<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\services;

use oneplugin\onepluginfields\OnePluginFields;
use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\Template;
use Embed\Adapters\Adapter;
use Embed\Embed;
use DOMDocument;
use GuzzleHttp\Client;
use oneplugin\onepluginfields\records\OnePluginFieldsCategory;
use oneplugin\onepluginfields\records\OnePluginFieldsSVGIcon;
use oneplugin\onepluginfields\records\OnePluginFieldsAnimatedIcon;
use oneplugin\onepluginfields\records\OnePluginFieldsVersion;
use oneplugin\onepluginfields\records\OnePluginFieldsOptimizedImage;
use oneplugin\onepluginfields\jobs\OptimizeImageJob;

/**
 * OnePluginFieldsService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Jagadeesh Vijayakumar
 * @package   OnePluginFields
 * @since     1.0.0
 */
class OnePluginFieldsService extends Component
{
    const SERVER_URL = 'https://dev.oneplugin.co';

    // Public Methods
    // =========================================================================

    public function videoPreviewService($url, array $options = [])
    {

        try {
            array_multisort($options);

            /** @var Adapter $media */
            $media = Embed::create($url, $options);

            if (!empty($media) && !isset($media->code)) {

                $media->code = "<iframe src='$url' width='100%' frameborder='0' scrolling='yes'></iframe>";
            }
        } finally {
            if (empty($media)) {
                $media = new class {
                    // Returns NULL for calls to props
                    public function __call(string $name, array $arguments)
                    {
                        return null;
                    }
                };
            }

            // Wrapping to be safe :)
            try {
                $html = $media->code;
                $dom = new DOMDocument;
                $dom->loadHTML($html);

                $iframe = $dom->getElementsByTagName('iframe')->item(0);
                $src = $iframe->getAttribute('src');
                //$src = $this->manageGDPR($src);

                if (!empty($options['params'])) {
                    foreach ((array)$options['params'] as $key => $value) {
                        $src = preg_replace('/\?(.*)$/i', '?' . $key . '=' . $value . '&${1}', $src);
                    }
                }

                // Scrolling - Override
                if (!empty($options['scrolling']) ) {
                    $iframe->setAttribute('scrolling', $options['scrolling']);
                }

                // Autoplay
                if (!empty($options['autoplay']) && strpos($html, 'autoplay=') === false && $src) {
                    $src = preg_replace('/\?(.*)$/i', '?autoplay=' . (!!$options['autoplay'] ? '1' : '0') . '&${1}', $src);
                }

                // Width - Override
                if (!empty($options['width']) ) {
                    $iframe->setAttribute('width', $options['width']);
                }

                // Height - Override
                if (!empty($options['height'])) {
                    $iframe->setAttribute('height', $options['height']);
                }

                // Looping
                if (!empty($options['loop']) && strpos($html, 'loop=') === false && $src) {
                    $src = preg_replace('/\?(.*)$/i', '?loop=' . (!!$options['loop'] ? '1' : '0') . '&${1}', $src);
                }

                // Autopause
                if (!empty($options['autopause']) && strpos($html, 'autopause=') === false && $src) {
                    $src = preg_replace('/\?(.*)$/i', '?autopause=' . (!!$options['autopause'] ? '1' : '0') . '&${1}', $src);
                }

                // Rel
                if (!empty($options['rel']) && strpos($html, 'rel=') === false && $src) {
                    $src = preg_replace('/\?(.*)$/i', '?rel=' . (!!$options['rel'] ? '1' : '0') . '&${1}', $src);
                }

                if (!empty($options['attributes'])) {
                    foreach ((array)$options['attributes'] as $key => $value) {
                        $iframe->setAttribute($key, $value);
                    }
                }

                $iframe->setAttribute('src', $src);
                $media->code = $dom->saveXML($iframe, LIBXML_NOEMPTYTAG);
            } catch (\Exception $exception) {
                Craft::info($exception->getMessage(), 'one-plugin-fields');
            }
            finally {

                return $media->code;
            }
        }
    }
    
    public function addRegenerateAllImageOptimizeJob(){

        $queue = Craft::$app->getQueue();
        $assets = OnePluginFieldsOptimizedImage::find()->all();
        foreach($assets as $asset){

            Craft::$app->db->createCommand()
            ->upsert(OnePluginFieldsOptimizedImage::tableName(), [
                'content' => '',
                'assetId' => $asset->assetId
            ], true, [], true)
            ->execute();

            $jobId = $queue->push(new OptimizeImageJob([
                'description' => Craft::t('one-plugin-fields', 'OnePlugin Fields - Job for optimizing image with id {id}', ['id' => $asset->assetId]),
                'assetId' => $asset->assetId,
                'force' => true
            ]));
        }
    }

    public function addImageOptimizeJob($assetId, $force,$runQueue = false){

        //TODO - check whether same job exists
        
        if($force){ //Make sure the content is cleared
            Craft::$app->db->createCommand()
                    ->upsert(OnePluginFieldsOptimizedImage::tableName(), [
                        'content' => '',
                        'assetId' => $assetId
                    ], true, [], true)
                    ->execute();
        }

        $queue = Craft::$app->getQueue();
        $jobId = $queue->push(new OptimizeImageJob([
            'description' => Craft::t('one-plugin-fields', 'OnePlugin Fields - Job for optimizing image with id {id}', ['id' => $assetId]),
            'assetId' => $assetId,
            'force' => $force
        ]));

        if($runQueue){
            App::maxPowerCaptain();
            Craft::$app->getQueue()->run();
        }
    }

    public function checkForUpdates( $current_version)
    {
        $client = new Client();

        $response = $client->request('GET', self::SERVER_URL . '/api/update/' . $current_version);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    public function downloadLatestVersion( $json)
    {
        $client = new Client();

        $response = $client->request('GET', self::SERVER_URL . $json['json_path']);
        $response = json_decode($response->getBody(), true);
        $latest_version = '1.0';
        
        foreach ($response as $version => $value) {
            $latest_version = $version;
            $categories = $value['categories'];
            $svgIcons = $value['svg'];
            $animatedIcons = $value['animatedicon'];

            foreach ($categories as $category) {
                $type = 'svg';
                if($category['type'] == 'ANIMATEDICON'){
                    $type = 'aicon';
                }
                $parent_id = 0;
                if( !empty($category['parent_id'])){
                    $parent_id = $category['parent_id'];
                }
                $cat = OnePluginFieldsCategory::find()->where(['id' => $category['id']] )->all();
                if( count($cat) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginFieldsCategory::tableName(), [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $type,
                        'count' => 0,
                        'parent_id' => $parent_id,
                    ],'id=' . $category['id']);
                    $command->execute();
                }
                else{
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginFieldsCategory::tableName(), [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $type,
                        'count' => 0,
                        'parent_id' => $parent_id,
                    ]);
                    $command->execute();
                }
            }

            foreach ($svgIcons as $svgIcon) {
                $svgs = OnePluginFieldsSVGIcon::find()->where(['name' => $svgIcon['fname']] )->all();
                $tags = '';
                if( isset($svgIcon['tags']) )
                    $tags = $svgIcon['tags'];
                if( count($svgs) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginFieldsSVGIcon::tableName(), [
                        'category' => $svgIcon['cid'],
                        'name' => $svgIcon['fname'],
                        'title' => $svgIcon['name'],
                        'description' => ' ',
                        'data' => $svgIcon['data'],
                        'tags' => $tags
                    ],'name = \'' . $svgIcon['fname'] . '\'');
                    $command->execute();
                }
                else {
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginFieldsSVGIcon::tableName(), [
                        'category' => $svgIcon['cid'],
                        'name' => $svgIcon['fname'],
                        'title' => $svgIcon['name'],
                        'description' => ' ',
                        'data' => $svgIcon['data'],
                        'tags' => $tags
                    ]);
                    $command->execute();
                }
            }

            foreach ($animatedIcons as $animatedIcon) {
                $data_loop = !empty($animatedIcon['data-loop'])?$animatedIcon['data-loop']:'';
                $data_morph = !empty($animatedIcon['data-morph'])?$animatedIcon['data-morph']:'';

                $aicons = OnePluginFieldsAnimatedIcon::find()->where(['name' => $animatedIcon['fname']] )->all();
                $tags = '';
                if( isset($animatedIcon['tags']) )
                    $tags = $animatedIcon['tags'];
                if( count($aicons) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginFieldsAnimatedIcon::tableName(), [
                        'category' => $animatedIcon['cid'],
                        'name' => $animatedIcon['fname'],
                        'title' => $animatedIcon['name'],
                        'description' => ' ',
                        'data_loop' => $data_loop,
                        'data_morph' => $data_morph,
                        'tags' => $tags
                    ],'name = \'' . $animatedIcon['fname'] . '\'');
                    $command->execute();
                }
                else{
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginFieldsAnimatedIcon::tableName(), [
                        'category' => $animatedIcon['cid'],
                        'name' => $animatedIcon['fname'],
                        'title' => $animatedIcon['name'],
                        'description' => ' ',
                        'data_loop' => $data_loop,
                        'data_morph' => $data_morph,
                        'tags' => $tags
                    ]);
                    $command->execute();
                }
            }

            Craft::$app->getDb()->createCommand("update onepluginfields_category set count = (select count(id) from onepluginfields_svg_icon where onepluginfields_svg_icon.category = onepluginfields_category.id) where onepluginfields_category.type = 'svg'")->execute();
            Craft::$app->getDb()->createCommand("update onepluginfields_category set count = (select count(id) from onepluginfields_animated_icon where onepluginfields_animated_icon.category = onepluginfields_category.id) where onepluginfields_category.type = 'aicon'")->execute();
            Craft::$app->getDb()->createCommand("update onepluginfields_animated_icon set onepluginfields_animated_icon.aloop = true where TRIM(data_loop) <> ''")->execute();
            Craft::$app->getDb()->createCommand("update onepluginfields_animated_icon set onepluginfields_animated_icon.amorph = true where TRIM(data_morph) <> ''")->execute();
            Craft::$app->plugins->savePluginSettings(OnePluginFields::$plugin, ['newContentPackAvailable'=>false]);
        }

        $command = Craft::$app->getDb()->createCommand()->update(OnePluginFieldsVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();
        return @['success' => true];
    }
    
}
