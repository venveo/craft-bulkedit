<?php

namespace venveo\bulkedit\models;

use craft\base\Model;
use craft\validators\SiteIdValidator;
use venveo\bulkedit\enums\FieldType;
use yii\validators\StringValidator;

class FieldConfig extends Model
{
    /** @var string|null The field or attribute handle */
    public ?string $handle = null;
    
    /** @var int|null If we have a field ID, store it here */
    public ?int $fieldId = null;

    /** 
     * @var string|null
     * @see \venveo\bulkedit\enums\FieldType
     */
    public ?string $type = null;

    /**
     * @var string|null The serialized field value
     */
    public ?string $serializedValue = null;

    /**
     * @var string|null
     */
    public ?string $strategy = null;

    /**
     * @var string|null Any additional options for the field strategy
     */
    public ?string $strategyOptions = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fieldId', 'siteId'], 'number', 'integerOnly' => true];
        $rules[] = [['siteId'], SiteIdValidator::class];
        $rules[] = [['handle', 'type', 'strategy'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['type'], 'in', 'range' => FieldType::asArray()];
//        $rules[] = [['strategy'], 'in', 'range' => FieldType::asArray()];
        return $rules;
    }
}