<?php

namespace venveo\bulkedit\models;

use craft\base\Model;
use craft\elements\User;

/**
 *
 * @property-read \craft\elements\User|null $owner
 */
class EditContext extends Model
{
    public ?int $siteId = null;
    public ?int $ownerId = null;
    /** @var int[] $elementIds */
    public array $elementIds = [];

    public ?int $total = null;

    /**
     * @var FieldConfig[]
     */
    public $fieldConfigs = [];

    /**
     * @return User|null
     */
    public function getOwner(): ?User
    {
        return $this->ownerId !== null ? \Craft::$app->users->getUserById($this->ownerId) : null;
    }
}