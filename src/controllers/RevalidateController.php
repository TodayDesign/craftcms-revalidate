<?php

namespace today\revalidate\controllers;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use today\revalidate\Revalidate;
use today\revalidate\utilities\RevalidateUtility;

class RevalidateController extends Controller
{
    public function actionRevalidateAll()
    {
        $this->requirePostRequest();

        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->revalidateAll();

        return $this->redirect(UrlHelper::url('utilities/'.RevalidateUtility::id()));
    }

    public function actionRevalidateSiteData()
    {
        $this->requirePostRequest();

        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->revalidateSiteData();

        return $this->redirect(UrlHelper::url('utilities/'.RevalidateUtility::id()));
    }

    public function actionDeploy()
    {
        $this->requirePostRequest();

        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->deploy();

        return $this->redirect(UrlHelper::url('utilities/'.RevalidateUtility::id()));
    }
}
