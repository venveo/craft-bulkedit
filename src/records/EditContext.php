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
    public static function tableName()
    {
        return '{{%bulkedit_editcontext}}';
    }


    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'ownerId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }


    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getHistoryItems(): ActiveQueryInterface
    {
        return $this->hasMany(History::class, ['contextId' => 'id']);
    }
}
