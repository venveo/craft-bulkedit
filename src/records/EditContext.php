<?php


namespace venveo\bulkedit\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use craft\records\User;
use yii\db\ActiveQueryInterface;

/**
 * @property int|null ownerId
 * @property integer siteId
 * @property string elementIds
 * @property ActiveQueryInterface $site
 * @property User $owner
 * @property ActiveQueryInterface $historyItems
 * @property string fieldIds
 * @property string elementType
 * @property integer id
 */
class EditContext extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%bulkedit_editcontext}}';
    }


    /**
     * @return \craft\db\ActiveQuery The relational query object.
     */
    public function getOwner(): \craft\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'ownerId']);
    }

    /**
     * @return \craft\db\ActiveQuery The relational query object.
     */
    public function getSite(): \craft\db\ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }


    /**
     * @return \craft\db\ActiveQuery The relational query object.
     */
    public function getHistoryItems(): \craft\db\ActiveQuery
    {
        return $this->hasMany(History::class, ['contextId' => 'id']);
    }
}
