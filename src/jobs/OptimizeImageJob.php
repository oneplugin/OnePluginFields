<?php

/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\console\Application as ConsoleApplication;
use craft\db\Paginator;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\helpers\App;
use craft\queue\BaseJob;
use craft\models\AssetTransform;
use craft\helpers\Image;
use Exception;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\records\OnePluginFieldsOptimizedImage as OnePluginFieldsOptimizedImageRecord;
use oneplugin\onepluginfields\models\OnePluginFieldsOptimizedImage;
use oneplugin\onepluginfields\models\Settings;

class OptimizeImageJob extends BaseJob
{
    /**
     * @var Asset - The asset Id of the image
     */
    public $assetId;

    /**
     * @var force - force asset generation
     */
    public $force;

    public function execute($queue)
    {
        Craft::$app->getTemplateCaches()->deleteCachesByElementType(Asset::class);

        $asset = Craft::$app->getAssets()->getAssetById($this->assetId);
        if( $asset ){
            $assets = OnePluginFieldsOptimizedImageRecord::find()->where(['assetId' => $this->assetId] )->all();
            if( count($assets) > 0 && !$this->force){
                return;
            }

            $model = $this->generateOptimizedImage($asset, $this->force);
            $json = json_encode($model);
            Craft::$app->db->createCommand()
                    ->upsert(OnePluginFieldsOptimizedImageRecord::tableName(), [
                        'content' => $json,
                        'assetId' => $asset->getId()
                    ], true, [], true)
                    ->execute();
            
        }
        else{
            OnePluginFieldsOptimizedImageRecord::find()->where(['assetId' => $this->assetId])->one()->delete();
        }
    }

    private function generateOptimizedImage(Asset $asset, $force){

        $settings = OnePluginFields::$plugin->getSettings();
        $imageAspect = $asset->width / $asset->height;
        $model = new OnePluginFieldsOptimizedImage('');

        $inputFormat = $asset->extension;
        $outputFormat = '';
        if( $settings->opOutputFormat == 'same'){
            $outputFormat = $inputFormat;
        }
        else{
            $outputFormat = $settings->opOutputFormat;
        }
        if (Image::canManipulateAsImage($outputFormat) && Image::canManipulateAsImage($inputFormat) && $asset->width > 0 && $asset->height > 0 ){
            foreach( $settings->opImageVariants as $size ){
                $opWidth = (int)$size['opWidth'];
                $opHeight = (int)((int)$size['opWidth'] / $imageAspect);
                try{
                    try {
                        $transform = new AssetTransform();
                        $transform->format = $outputFormat;
                        $transform->quality = $size['opQuality'];
                        $transform->width = $opWidth;
                        $transform->height = $opHeight;
                        $transform->interlace = 'line'; //for progressive jpgs

                        $transforms = Craft::$app->getAssetTransforms();
                        $index = $transforms->getTransformIndex($asset, $transform);
                        $index->fileExists = 0;
                        $transforms->storeTransformIndexData($index);
                        $volume = $asset->getVolume();
                        $transformPath = $asset->folderPath . $transforms->getTransformSubpath($asset, $index);
                        $volume->deleteFile($transformPath);
                    } 
                    catch (\Throwable $e) {
                        $message = 'Failed to delete transform: '.$e->getMessage();
                        Craft::error($message, __METHOD__);
                        $model->errors[] = $message;
                    }

                    if( !$settings->opUpscale && ($asset->width < $opWidth || $asset->height < $opHeight )){
                        $model->imageUrls[$opWidth] = ['url' => '','width'=>$transform->width,'height'=>$transform->height,'size'=>'0'];
                        continue;
                    }

                    $transform = new AssetTransform();
                    $transform->format = $outputFormat;
                    $transform->quality = $size['opQuality'];
                    $transform->width = $opWidth;
                    $transform->height = $opHeight;
                    $transform->interlace = 'line'; //for progressive jpgs

                    list($image,$errors) = $this->generateImageVariant($asset, $transform);
                    $model->imageUrls[$opWidth] = $image;
                    if( !empty($errors) ){
                        $model->errors[] = $errors;
                    }

                    if( $outputFormat == 'webp'){ //Old version of Safari browser doesn't support webp. We need a fallback in that case.
                        $transform->format = 'jpg';
                        list($image,$errors) = $this->generateImageVariant($asset, $transform);
                        $model->fallbackImageUrls[$opWidth] = $image;
                        if( !empty($errors) ){
                            $model->errors[] = $errors;
                        }
                    }
                }
                catch(\Throwable $e) {
                    $message = 'Failed to create transform: '.$e->getMessage();
                    Craft::error($message, __METHOD__);
                    $model->errors[] = $message;
                }
            }
            $model->originalUrl = $asset->getUrl();
            $model->width = $asset->width;
            $model->height = $asset->height;
            $model->name = $asset->title;
            $model->extension = $outputFormat;
        }
        else{
            return null;
        }
        return $model;
    }

    private function generateImageVariant($asset,$transform): array{

        $image = '';
        $filesize = 0;
        $errors = '';
        try{
            $assetService = Craft::$app->getAssets();
            $url = $assetService->getAssetUrl($asset, $transform, true);
            if( $url ){
                
                if( ini_get('allow_url_fopen') ) {
                    $headers = get_headers($url, true);
                    if( isset($headers['Content-Length']) ){
                        $filesize = $headers['Content-Length'];
                    }
                } 
                $image = ['url' => $url,'width'=>$transform->width,'height'=>$transform->height,'size'=>$filesize];
            }
        }
        catch(\Throwable $e) {
            $errors = 'Failed to create transform: '.$e->getMessage();
            Craft::error($errors, __METHOD__);
        }
        finally{
            return [$image,$errors];
        }
        
    }
    private function printTransform($transform){

        return '_' . ($transform->width ?: 'AUTO') . 'x' . ($transform->height ?: 'AUTO') .
            '_' . $transform->mode .
            '_' . $transform->position .
            ($transform->quality ? '_' . $transform->quality : '') .
            '_' . $transform->interlace;
    }
    private function generatePlaceholderImage(Asset $asset){



    }

}