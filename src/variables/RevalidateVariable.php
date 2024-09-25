<?php
namespace today\revalidate;

use today\revalidate\Revalidate;

class RevalidateVariable
{
    public function getLatestDeploymentStatus()
    {
        return Revalidate::getInstance()->revalidate->getLatestDeploymentStatus();
    }
}
