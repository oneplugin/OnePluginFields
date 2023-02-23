<?php

/**
 * OnePlugin Fields plugin for Craft CMS 3.x
 *
 * OnePlugin Fields lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginfields\migrations;

use Craft;

use craft\db\Migration;
use craft\helpers\Json;
use oneplugin\onepluginfields\OnePluginFields;
use oneplugin\onepluginfields\records\OnePluginFieldsSVGIcon;
use oneplugin\onepluginfields\records\OnePluginFieldsVersion;
use oneplugin\onepluginfields\records\OnePluginFieldsCategory;
use oneplugin\onepluginfields\records\OnePluginFieldsAnimatedIcon;

class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $this->dropTableIfExists('{{%onepluginfields_config}}');
        $this->createTable('{{%onepluginfields_config}}', [
            'id' => $this->primaryKey(),
            'content_version_number' => $this->string(256)->notNull(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);
        $this->dropTableIfExists('{{%onepluginfields_category}}');
        $this->createTable('{{%onepluginfields_category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(256)->notNull(),
            'type' => $this->string(256)->notNull(),
            'parent_id' => $this->integer(),
            'count' => $this->integer(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginfields_svg_icon}}');
        $this->createTable('{{%onepluginfields_svg_icon}}', [
            'id' => $this->primaryKey(),
            'category' => $this->integer()->notNull(),
            'name' => $this->string(256)->notNull(),
            'title' => $this->string(256)->notNull(),
            'description' => $this->text(),
            'data' => $this->mediumText(),
            'tags' => $this->mediumText(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginfields_animated_icon}}');
        $this->createTable('{{%onepluginfields_animated_icon}}', [
            'id' => $this->primaryKey(),
            'category' => $this->integer()->notNull(),
            'name' => $this->string(256)->notNull(),
            'title' => $this->string(256)->notNull(),
            'description' => $this->text(),
            'data_loop' => $this->mediumText(),
            'data_morph' => $this->mediumText(),
            'tags' => $this->mediumText(),
            'aloop' => $this->boolean()->defaultValue(false),
            'amorph' => $this->boolean()->defaultValue(false),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginfields_optimized_image}}');
        $this->createTable('{{%onepluginfields_optimized_image}}', [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'content' => $this->mediumText(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);

        $this->dropTableIfExists('{{%onepluginfields_svg_icon_packs}}');
        $this->createTable('{{%onepluginfields_svg_icon_packs}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'handle' => $this->string(),
            'category' => $this->integer()->notNull(),
            'count' => $this->string()->notNull(),
            'dateArchived' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%onepluginfields_config}}', 'id', true);
        $this->createIndex(null, '{{%onepluginfields_svg_icon}}', 'id', true);
        $this->createIndex(null, '{{%onepluginfields_animated_icon}}', 'id', true);
        $this->createIndex(null, '{{%onepluginfields_category}}', 'id', true);
        $this->createIndex(null, '{{%onepluginfields_optimized_image}}', 'id', true);
        $this->createIndex(null, '{{%onepluginfields_optimized_image}}', 'assetId', true);
        $this->createIndex(null, '{{%onepluginfields_svg_icon_packs}}', 'category', false);
        
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%onepluginfields_animated_icon}}', ['category'], '{{%onepluginfields_category}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%onepluginfields_svg_icon}}', ['category'], '{{%onepluginfields_category}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%onepluginfields_svg_icon_packs}}', ['category'], '{{%onepluginfields_category}}', ['id'], 'CASCADE', null);
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
        $command = $this->db->createCommand()->insert(OnePluginFieldsVersion::tableName(), [
            'content_version_number' => '1.0'
        ]);
        $command->execute();

        $dir = OnePluginFields::getInstance()->getBasePath();
        $path = $dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'data.json';
        $data = Json::decode(file_get_contents($path));
        $latest_version = '';
        foreach ($data as $version => $value) {
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
                $command = $this->db->createCommand()->insert(OnePluginFieldsCategory::tableName(), [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'type' => $type,
                    'count' => 0,
                    'parent_id' => $parent_id,
                ]);
                $command->execute();
            }

            foreach ($svgIcons as $svgIcon) {
                $command = $this->db->createCommand()->insert(OnePluginFieldsSVGIcon::tableName(), [
                    'category' => $svgIcon['cid'],
                    'name' => $svgIcon['fname'],
                    'title' => $svgIcon['name'],
                    'description' => ' ',
                    'data' => $svgIcon['data'],
                    'tags' => $svgIcon['tags']
                ]);
                $command->execute();
            }

            foreach ($animatedIcons as $animatedIcon) {
                $data_loop = !empty($animatedIcon['data-loop'])?$animatedIcon['data-loop']:'';
                $data_morph = !empty($animatedIcon['data-morph'])?$animatedIcon['data-morph']:'';

                $command = $this->db->createCommand()->insert(OnePluginFieldsAnimatedIcon::tableName(), [
                    'category' => $animatedIcon['cid'],
                    'name' => $animatedIcon['fname'],
                    'title' => $animatedIcon['name'],
                    'description' => ' ',
                    'data_loop' => $data_loop,
                    'data_morph' => $data_morph,
                    'tags' => $animatedIcon['tags']
                ]);
                $command->execute();
            }
        }

        $command = $this->db->createCommand()->update(OnePluginFieldsVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();

        $this->db->createCommand("update onepluginfields_category set count = (select count(id) from onepluginfields_svg_icon where onepluginfields_svg_icon.category = onepluginfields_category.id) where onepluginfields_category.type = 'svg'")->execute();
        $this->db->createCommand("update onepluginfields_category set count = (select count(id) from onepluginfields_animated_icon where onepluginfields_animated_icon.category = onepluginfields_category.id) where onepluginfields_category.type = 'aicon'")->execute();
        $this->db->createCommand("update onepluginfields_animated_icon set onepluginfields_animated_icon.aloop = true where TRIM(data_loop) <> ''")->execute();
        $this->db->createCommand("update onepluginfields_animated_icon set onepluginfields_animated_icon.amorph = true where TRIM(data_morph) <> ''")->execute();
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%onepluginfields_config}}');
        $this->dropTableIfExists('{{%onepluginfields_svg_icon_packs}}');        
        $this->dropTableIfExists('{{%onepluginfields_animated_icon}}');
        $this->dropTableIfExists('{{%onepluginfields_svg_icon}}');
        $this->dropTableIfExists('{{%onepluginfields_category}}');
        $this->dropTableIfExists('{{%onepluginfields_optimized_image}}');
        $this->db->createCommand("delete from " . Craft::$app->getQueue()->tableName . " where description like 'OnePlugin Fields%' ")->execute();
    }
}
