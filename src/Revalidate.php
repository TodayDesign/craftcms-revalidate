<?php

namespace today\revalidate;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Category;
use craft\events\ElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Elements;
use craft\services\Utilities;
use today\revalidate\models\Settings;
use today\revalidate\services\RevalidateService;
use today\revalidate\utilities\RevalidateUtility;
use yii\base\Event;
use yii\base\ActionEvent;
use craft\events\ModelEvent;
use verbb\navigation\elements\Node;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\web\Controller;

/**
 * Revalidate plugin
 *
 * @method static Revalidate getInstance()
 * @method Settings getSettings()
 */
class Revalidate extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => [
                'revalidate' => RevalidateService::class,
            ],
        ];
    }

    public function init()
    {
        parent::init();
        // self::$plugin = $this;

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            $this->registerUtility();
            $this->registerVercelWebhook();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
        if ($this->getSettings()->sync) {
            $events = [
                [Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT],
                [Elements::class, Elements::EVENT_AFTER_RESTORE_ELEMENT],
                [Elements::class, Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI],
                [Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT],
            ];

            foreach ($events as $event) {
                Event::on(
                    $event[0],
                    $event[1],
                    function (ElementEvent $event) {
                        // Make sure element is
                        $this->getService()->revalidateElement($event->element);
                    }
                );
            }

            // Add Events for the URL Rules
            Event::on(
                UrlManager::class,
                UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                function(RegisterUrlRulesEvent $event) {
                    $event->rules = array_merge($event->rules, [
                        'GET api/scheduled-entries' => 'revalidate/scheduled-entries/resolve-request',
                    ]);
                }
            );
        }
    }

    private function registerUtility(): void
    {
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = RevalidateUtility::class;
            }
        );
    }

    private function registerVercelWebhook(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
              function (RegisterUrlRulesEvent $event) {
                  $event->rules = array_merge($event->rules, [
                      'POST api/vercel-webhook' => 'revalidate/webhook/vercel',
                  ]);
              }
            }
        );

        Event::on(
            Controller::class,
            Controller::EVENT_BEFORE_ACTION,
            function (ActionEvent $event) {
                $request = Craft::$app->getRequest();
                if ($request->getIsPost() && $request->getUrl() === 'api/vercel-webhook') {
                    $request->enableCsrfValidation = false;
                }
            }
        );
    }

    public function getService()
    {
        return $this->get('revalidate');
    }
}
