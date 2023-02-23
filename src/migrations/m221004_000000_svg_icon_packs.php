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

use craft\db\Migration;

class m221004_000000_svg_icon_packs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%onepluginfields_svg_icon_packs}}')) {
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

            $this->createIndex(null, '{{%onepluginfields_svg_icon_packs}}', 'category', false);
            $this->addForeignKey(null, '{{%onepluginfields_svg_icon_packs}}', ['category'], '{{%onepluginfields_category}}', ['id'], 'CASCADE', null);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%onepluginfields_svg_icon_packs}}');
        return true;
    }
}
