<?php

namespace today\revalidate\utilities;

use Craft;
use craft\base\Utility;
use today\revalidate\Revalidate;

class RevalidateUtility extends Utility
{
    public static function displayName(): string
    {
        return 'Revalidate';
    }

    public static function id(): string
    {
        return 'revalidate-utility';
    }

    public static function iconPath(): string
    {
        return Craft::getAlias('@app/icons/trash.svg');
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        return $view->renderTemplate('revalidate/_utility.twig');
    }
}
