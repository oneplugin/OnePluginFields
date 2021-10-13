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
use craft\queue\BaseJob;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\records\OnePluginFieldsVersion;
use oneplugin\onepluginfields\services\OnePluginFieldsService;

class ContentSyncJob extends BaseJob
{

    public function execute($queue)
    {
        $settings = OnePluginFields::$plugin->getSettings();
        if( $settings->newContentPackAvailable ){
            $this->addJob();
            return;
        }
        $version = OnePluginFieldsVersion::latest_version();
        $response = OnePluginFields::$plugin->onePluginFieldsService->checkForUpdates($version);
        if( $response['updates'] ){
            Craft::$app->plugins->savePluginSettings(OnePluginFields::$plugin, ['newContentPackAvailable'=>true]);
        }
        $this->addJob();
    }

    private function addJob(){
        //This function adds a job for checking availability of new content after 24 hours.

        $queue = Craft::$app->getQueue();
        $jobId = $queue->priority(1024)
                        ->delay(6 * 60 * 60)
                        ->ttr(300)
                        ->push(new ContentSyncJob([
            'description' => Craft::t('one-plugin-fields', 'OnePlugin Fields - Job for checking availability of new content packs')
        ]));
    }
}