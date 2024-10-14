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
            ['type', 'in', 'range' => ['deployment.succeeded', 'deployment.created', 'deployment.error', 'deployment.canceled']],
            [['dateCreated', 'dateUpdated'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }

    public function getLabel()
    {
        switch ($this->type) {
            case 'deployment.succeeded':
                return 'Success';
            case 'deployment.created':
                return 'Deploying';
            case 'deployment.error':
            case 'deployment.canceled':
                return 'Failed';
            default:
                return 'Unknown';
        }
    }

    public function getColor()
    {
        switch ($this->type) {
            case 'deployment.succeeded':
                return 'var(--enabled-color)';
            case 'deployment.created':
                return '#F3BA48';
            case 'deployment.error':
            case 'deployment.canceled':
                return 'var(--disabled-color)';
            default:
                return 'var(--grey-500)';
        }
    }
}
