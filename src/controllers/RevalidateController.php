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

        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->revalidateAll();
        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->deploy();

        return $this->redirect(UrlHelper::url('utilities/'.RevalidateUtility::id()));
    }

    public function actionRevalidatePaths()
    {
        $this->requirePostRequest();

        $paths = Craft::$app->getRequest()->getBodyParam('paths');

        // If no paths, add error message
        if (!$paths) {
            Craft::$app->getSession()->setError('No paths to revalidate');
            return null;
        }

        // Get first item of the array, then join the arrays
        $paths = array_map(function($path) {
            return $path[0];
        }, $paths);

        // Deduplicate
        $paths = array_unique($paths);

        $siteUrl = Craft::$app->getRequest()->getBodyParam('siteUrl');

        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->revalidate($siteUrl, ['paths' => $paths, 'tags' => []]);

        return $this->redirect(UrlHelper::url('utilities/'.RevalidateUtility::id()));
    }

    public function actionRevalidateTags()
    {
        $this->requirePostRequest();

        $tags = Craft::$app->getRequest()->getBodyParam('tags');

        // If no tags, add error message
        if (!$tags) {
            Craft::$app->getSession()->setError('No tags to revalidate');
            return null;
        }

        // Get first item of the array, then join the arrays
        $tags = array_map(function($tag) {
            return $tag[0];
        }, $tags);

        // Deduplicate
        $tags = array_unique($tags);

        $siteUrl = Craft::$app->getRequest()->getBodyParam('siteUrl');

        Craft::$app->getPlugins()->getPlugin('revalidate')->getService()->revalidate($siteUrl, ['paths' => [], 'tags' => $tags]);

        return $this->redirect(UrlHelper::url('utilities/'.RevalidateUtility::id()));
    }
}
