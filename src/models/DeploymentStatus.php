<?php
namespace today\revalidate\models;

use craft\base\Model;

class DeploymentStatus extends Model
{
    public $id;
    public $type;
    public $createdAt;

    public function rules()
    {
        return [
            [['type', 'createdAt'], 'required'],
            ['type', 'in', 'range' => ['succeeded', 'created', 'error', 'canceled']],
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
                return 'green';
            case 'created':
                return 'yellow';
            case 'error':
            case 'canceled':
                return 'red';
            default:
                return 'gray';
        }
    }
}
