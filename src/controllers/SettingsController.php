<?php


namespace oneplugin\onepluginfields\controllers;

use oneplugin\onepluginfields\fields\AnimatedIconsAssets;

use Craft;
use craft\web\Controller;
use craft\web\View;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\helpers\StringHelper;
use oneplugin\onepluginfields\records\OnePluginFieldsVersion;
use oneplugin\onepluginfields\services\OnePluginFieldsService;
use yii\web\Response;

class SettingsController extends Controller
{

    public $plugin;

    public function init()
    {
        $this->plugin = OnePluginFields::$plugin;
        parent::init();
    }

    public function actionIndex(): Response
    {
        $settings = $this->plugin->getSettings();

        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/dist',
            true
        );
        
        Craft::$app->getView()->registerCssFile($baseAssetsUrl . '/css/onepluginfields.min.css');
        Craft::$app->getView()->registerJsFile($baseAssetsUrl . '/js/spectrum.min.js',['depends' => CpAsset::class]);

        return $this->renderTemplate('one-plugin-fields/settings/_general', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSync(): Response
    {
        $settings = $this->plugin->getSettings();
        $version = OnePluginFieldsVersion::latest_version();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginfields/assetbundles/onepluginfields/dist',
            true
        );
        Craft::$app->getView()->registerJsFile($baseAssetsUrl . '/js/party.min.js',['depends' => CpAsset::class]);
        return $this->renderTemplate('one-plugin-fields/settings/_sync', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings,
                    'version' => $version,
                    'formatted_version' => number_format((float)$version, 1, '.', '')
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $postData = Craft::$app->request->post('settings', []);

        $plugin = OnePluginFields::getInstance();
        $plugin->setSettings($postData);
        $settings = $this->plugin->getSettings();
        
        if (Craft::$app->plugins->savePluginSettings($plugin, $postData)) {
            Craft::$app->session->setNotice(OnePluginFields::t('Settings Saved'));

            $opHash = $this->generateOpHash($postData);
            if( $opHash != $settings->opSettingsHash){
                $this->plugin->onePluginFieldsService->addRegenerateAllImageOptimizeJob();
                Craft::$app->plugins->savePluginSettings($plugin, ['opSettingsHash'=>$opHash]);
            }
            return $this->redirectToPostedUrl();
        }

        $errors = $plugin->getSettings()->getErrors();
        Craft::$app->session->setError(
            implode("\n", StringHelper::flattenArrayValues($errors))
        );
    }

    public function actionCheckForUpdates()
    {
        $version = OnePluginFieldsVersion::latest_version();
        $response = $this->plugin->onePluginFieldsService->checkForUpdates($version);
        return $this->asJson($response);
    }

    public function actionDownloadFiles(){

        $version = OnePluginFieldsVersion::latest_version();
        $response = $this->plugin->onePluginFieldsService->checkForUpdates($version);
        return $this->asJson($this->plugin->onePluginFieldsService->downloadLatestVersion($response));

    }

    private function generateOpHash($postData){

        if( empty($postData['opUpscale'])){
            $postData['opUpscale'] =  '0';
        }
        $source = $postData['opOutputFormat'] . $postData['opUpscale'] . $this->implode_all('x',$postData['opImageVariants']);
        return md5($source);
    }

    private function implode_all($glue, $arr){            
        for ($i=0; $i<count($arr); $i++) {
            if (@is_array($arr[$i])) 
                $arr[$i] = $this->implode_all ($glue, $arr[$i]);
        }            
        return implode($glue, $arr);
    }
}