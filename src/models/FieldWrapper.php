<?php


namespace venveo\bulkedit\models;

use craft\base\Model;
use craft\records\User;

/**
 * @property int|null ownerId
 * @property integer siteId
 * @property string elementIds
 * @property \yii\db\ActiveQueryInterface $site
 * @property User $owner
 * @property \yii\db\ActiveQueryInterface $historyItems
 * @property string fieldIds
 * @property integer id
 */
class FieldWrapper extends Model
{
    public $field;
    public $strategy;
    public $layouts;
}
