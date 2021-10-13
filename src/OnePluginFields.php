<?php
/**
 * OnePluginFields plugin for Craft CMS 3.x
 *
 * OnePluginFields lets the Craft community embed rich contents on their website
 *
 * @link      https://guthub.com/
 * @copyright Copyright (c) 2021 Jagadeesh Vijayakumar
 */

namespace oneplugin\onepluginfields;

use oneplugin\onepluginfields\services\OnePluginFieldsService as OnePluginFieldsService;
use oneplugin\onepluginfields\variables\OnePluginFieldsVariable;
use oneplugin\onepluginfields\models\Settings;
use oneplugin\onepluginfields\fields\OnePluginFields as OnePluginFieldsField;
use oneplugin\onepluginfields\records\OnePluginFieldsOptimizedImage;
use oneplugin\onepluginfields\jobs\ContentSyncJob;

use Craft;
use craft\web\View;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use yii\base\Event;


use craft\elements\Asset;
use craft\services\AssetTransforms;
use craft\events\AssetTransformImageEvent;
use craft\events\ReplaceAssetEvent;
use craft\events\ElementEvent;
use craft\services\Assets;
use craft\services\Elements;
/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Jagadeesh Vijayakumar
 * @package   OnePluginFields
 * @since     1.0.0
 *
 * @property  OnePluginFieldsServiceService $onePluginFieldsService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class OnePluginFields extends Plugin
{
    // Static Properties
    // =========================================================================
    const TRANSLATION_CATEGORY = 'one-plugin-fields';
    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * OnePluginFields::$plugin
     *
     * @var OnePluginFields
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * OnePluginFields::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'onePluginFieldsService' => OnePluginFieldsService::class,
        ]);

        $this->initRoutes();

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                Craft::debug(
                    'Fields::EVENT_REGISTER_FIELD_TYPES',
                    __METHOD__
                );
                $event->types[] = OnePluginFieldsField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                Craft::debug(
                    'CraftVariable::EVENT_INIT',
                    __METHOD__
                );
                $variable = $event->sender;
                $variable->set('onePluginFields', OnePluginFieldsVariable::class);
            }
        );

        // Handle Assets::EVENT_AFTER_REPLACE_ASSET
        Event::on(
            Assets::class,
            Assets::EVENT_AFTER_REPLACE_ASSET,
            function (ReplaceAssetEvent $event) {
                Craft::debug(
                    'Assets::EVENT_AFTER_REPLACE_ASSET',
                    __METHOD__
                );
                $asset = $event->asset;
                $assets = OnePluginFieldsOptimizedImage::find()->where(['assetId' => $asset->id] )->all();
                if( count($assets) == 0 ){
                    return;
                }
                $this->onePluginFieldsService->addImageOptimizeJob($asset->id, true, true);
            }
        );

        // Handler: AssetTransforms::EVENT_AFTER_DELETE_TRANSFORMS
        Event::on(
            AssetTransforms::class,
            AssetTransforms::EVENT_AFTER_DELETE_TRANSFORMS,
            function (AssetTransformImageEvent $event) {
                Craft::debug(
                    'AssetTransforms::EVENT_AFTER_DELETE_TRANSFORMS',
                    __METHOD__
                );
                //TODO delete all transforms
            }
        );

        // Handler: Elements::EVENT_BEFORE_DELETE_ELEMENT
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_DELETE_ELEMENT,
            function (ElementEvent $event) {
                Craft::debug(
                    'Elements::EVENT_AFTER_DELETE_ELEMENT',
                    __METHOD__
                );
                if( $event->element instanceof Asset ){
                    $asset = $event->element;
                    $assets = OnePluginFieldsOptimizedImage::find()->where(['assetId' => $asset->id] )->all();
                    if( count($assets) == 0 ){
                        return;
                    }
                    OnePluginFieldsOptimizedImage::find()->where(['assetId' => $asset->id])->one()->delete();
                }
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                Craft::debug(
                    'Plugins::EVENT_AFTER_INSTALL_PLUGIN',
                    __METHOD__
                );
                if ($event->plugin === $this) {
                    // If installed plugin isn't OnePlugin Fields, bail
                    if ('one-plugin-fields' !== $event->plugin->handle) {
                        return;
                    }

                    $queue = Craft::$app->getQueue();
                    $jobId = $queue->priority(1024)
                                    ->delay(0)
                                    ->ttr(300)
                                    ->push(new ContentSyncJob([
                                        'description' => Craft::t('one-plugin-fields', 'OnePlugin Fields - Job for checking availability of new content packs')
                                    ]));

                    // If installed via console, no need for a redirect
                    if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                        return;
                    }
                    
                    // Redirect to the plugin's settings page (with a welcome message)
                    Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('one-plugin-fields/welcome'))->send();
                }
            }
        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'one-plugin-fields',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getPluginName()
    {
        $settings = $this->getSettings();
        return Craft::t('one-plugin-fields', $this->getSettings()->pluginName);
    }

    public function getCpNavItem()
    {
        $settings = $this->getSettings();
        $navItem = parent::getCpNavItem();
        $navItem['label'] = $this->getPluginName();
        if( $settings->newContentPackAvailable ){
            $navItem['badgeCount'] = 1;
        }
        $navItem['subnav']['settings'] = ['label' => Craft::t('one-plugin-fields', 'Settings'), 'url' => 'one-plugin-fields/settings'];
        if( $settings->newContentPackAvailable ){
            $navItem['subnav']['content-sync'] = ['label' => Craft::t('one-plugin-fields', 'Content Sync'), 'url' => 'one-plugin-fields/settings/sync','badgeCount' => 1];
        }
        else{
            $navItem['subnav']['content-sync'] = ['label' => Craft::t('one-plugin-fields', 'Content Sync'), 'url' => 'one-plugin-fields/settings/sync'];
        }
        return $navItem;
    }

    /**
     * @param string $message
     * @param array  $params
     * @param string $language
     *
     * @return string
     */
    public static function t(string $message, array $params = [], string $language = null): string
    {
        return \Craft::t(self::TRANSLATION_CATEGORY, $message, $params, $language);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Redirect to OnePlugin Fields settings
     *
     * @return $this|mixed|Response
     */
    public function getSettingsResponse()
    {
        $url = UrlHelper::cpUrl('one-plugin-fields/settings');
        return Craft::$app->getResponse()->redirect($url);
    }

    //Private functions
    private function initRoutes()
    {
        //link = controller/function

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {

            $event->rules['one-plugin-fields/'] = 'one-plugin-fields/one-plugin/index';
            $event->rules['one-plugin-fields/default'] = 'one-plugin-fields/one-plugin/index';

            $event->rules['one-plugin-fields/settings'] = 'one-plugin-fields/settings/index';
            $event->rules['one-plugin-fields/settings/sync'] = 'one-plugin-fields/settings/sync';
            $event->rules['one-plugin-fields/settings/save-settings'] = 'one-plugin-fields/settings/save-settings';
            $event->rules['one-plugin-fields/settings/check-for-updates'] = 'one-plugin-fields/settings/check-for-updates';
            $event->rules['one-plugin-fields/settings/download-files'] = 'one-plugin-fields/settings/download-files';

            $event->rules['one-plugin-fields/one-plugin/load'] = 'one-plugin-fields/one-plugin/load';
            $event->rules['one-plugin-fields/one-plugin/show'] = 'one-plugin-fields/one-plugin/show';
            $event->rules['one-plugin-fields/one-plugin/preview'] = 'one-plugin-fields/one-plugin/preview';
            $event->rules['one-plugin-fields/one-plugin/create-optimized-image'] = 'one-plugin-fields/one-plugin/create-optimized-image';
            $event->rules['one-plugin-fields/one-plugin/icons-by-category/<id:\d+>'] = 'one-plugin-fields/one-plugin/icons-by-category';
            $event->rules['one-plugin-fields/one-plugin/search-icons-svg/<text:\d+>'] = 'one-plugin-fields/one-plugin/search-icons-svg';
            $event->rules['one-plugin-fields/one-plugin/search-icons-aicon/<text:\d+>'] = 'one-plugin-fields/one-plugin/search-icons-aicon';
            $event->rules['one-plugin-fields/one-plugin/check-asset/<assetId:\d+>'] = 'one-plugin-fields/one-plugin/check-asset';

        });
    }

}
