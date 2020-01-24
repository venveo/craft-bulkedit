<?php


namespace venveo\bulkedit\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Field;
use craft\records\Site;
use yii\db\ActiveQueryInterface;

/**
 * @property integer fieldId
 * @property string status
 * @property ActiveQueryInterface $element
 * @property ActiveQueryInterface $context
 * @property Field $field
 * @property Site $site
 * @property integer id
 * @property string originalValue
 * @property string newValue
 * @property integer elementId
 * @property string strategy
 */
class History extends ActiveRecord
{
    /*
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bulkedit_history}}';
    }


    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getContext(): ActiveQueryInterface
    {
        return $this->hasOne(EditContext::class, ['id' => 'contextId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
