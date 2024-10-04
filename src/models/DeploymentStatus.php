<?php
namespace today\revalidate\models;

use craft\base\Model;

class DeploymentStatus extends Model
{
    public $id;
    public $type;
    public $createdAt;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    public function rules(): array
    {
        return [
            [['type', 'createdAt'], 'required'],
            ['type', 'in', 'range' => ['succeeded', 'created', 'error', 'canceled']],
            [['dateCreated', 'dateUpdated'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

    public function getLabel()
    {
        switch ($this->type) {
            case 'succeeded':
                return 'Success';
            case 'created':
                return 'Deploying';
            case 'error':
            case 'canceled':
                return 'Failed';
            default:
                return 'Unknown';
        }
    }

    public function getColor()
    {
        switch ($this->type) {
            case 'succeeded':
                return 'var(--enabled-color)';
            case 'created':
                return 'var(--pending-color)';
            case 'error':
            case 'canceled':
                return 'var(--disabled-color)';
            default:
                return 'var(--grey-500)';
        }
    }
}
