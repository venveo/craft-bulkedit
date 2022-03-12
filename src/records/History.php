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
    public static function tableName(): string
    {
        return '{{%bulkedit_history}}';
    }


    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getContext(): \craft\db\ActiveQuery
    {
        return $this->hasOne(EditContext::class, ['id' => 'contextId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): \craft\db\ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField(): \craft\db\ActiveQuery
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }

    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): \craft\db\ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
