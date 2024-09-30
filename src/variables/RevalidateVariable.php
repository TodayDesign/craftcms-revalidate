<?php
namespace today\revalidate\variables;

use today\revalidate\Revalidate;

class RevalidateVariable
{
    public function getLatestDeploymentStatus()
    {
        return Revalidate::getInstance()->revalidate->getLatestDeploymentStatus();
    }
}
