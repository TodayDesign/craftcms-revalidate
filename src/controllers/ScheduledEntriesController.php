<?php
/**
 * Subscription module for Craft CMS 4.x
 *
 * A module to manage the subscribers of delasign.com.
 *
 * @link      https://www.delasign.com
 * @copyright Copyright (c) 2022 Oscar de la Hera Gomez
 */

namespace today\revalidate\controllers;

/**
 * Get All Subscribers Controller
 *
 * This controller carries a single action responsible for processing GET requests
 * that wish to get the details for subscribers.
 * 
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your module’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Oscar de la Hera Gomez
 * @package   Subscription Module
 * @since     1.0.0
 */
use Craft;
use craft\helpers\App;
use craft\web\Controller;
use yii\web\HttpException;
use yii\web\Response;
// Import Entry to allow for entries to work
use craft\elements\Entry;

class ScheduledEntriesController extends Controller
{
    // Protected Properties
    // =========================================================================

     /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected int|bool|array $allowAnonymous = true;

    
    
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        // DISABLE CSRF PROTECTION TO ALLOW FOR RESTFUL REQUESTS THAT ARE NOT GET REQUESTS
        // =========================================================================
        // Disable protection for the action 'resolve-request'.
        // The actions must be in 'kebab-case'.
        if ($action->id === 'resolve-request') {
            $this->enableCsrfValidation = false;
        }
        // API KEY VALIDATION
        // =========================================================================
        // Get the API Key
        $apiKey = App::env('REVALIDATE_SECRET_TOKEN') || 'secret';

        // Get the API Key through the x-api-key parameter
        $key = Craft::$app->getRequest()->getParam('apiKey', '');

        // Verify provided key against API key
        if (empty($key) || empty($apiKey) || $key != $apiKey) {
            /* Throw a 403 - FORBIDDEN, if there 
            * - is no API Key in Craft
            * - there is no API Key in the request
            * - there API key does not match that of the request
            * If you wish to use a conventional HTTPException use the line below.
            * throw new HttpException(403, 'Unauthorised API key.');
            *
            * We prefer to use a standardized response.
            */
            $this->sendResponse(403, 'Unauthorised API key.', null);
            return false;
        }
        

        return parent::beforeAction($action);
    }
    
    /**
     * Handle a request going to our module's actionNewSubscriber URL,
     * e.g.: subscription/new-subscriber/resolve-request
     *
     * @return mixed
     */
    public function actionResolveRequest(): Response
    { 
        // Find entries with a post date that is within 5 minutes of the current time
        $entries = Entry::find()
            ->postDate(['and', '>=' . date('Y-m-d H:i:s', strtotime('-5 minutes')), '<=' . date('Y-m-d H:i:s')])
            ->status([Entry::STATUS_LIVE, Entry::STATUS_EXPIRED])
            ->all();

        // Resave the entries
        foreach ($entries as $entry) {
            Craft::$app->getElements()->saveElement($entry);

            // Run the queue so that the entries are indexed for Algolia if Scout is installed
            Craft::$app->queue->run();
        }

        return $this->sendResponse(200, null, $entries);
    }

    /**
     * Send a response based on a status code ($code), an error ($error) & a response ($response).
     *
     * @return array
     */

    public function sendResponse(int $code, mixed $error, mixed $response) {
        return $this->asJSON([
            'statusCode' => $code,
            'headers' => [
                "Access-Control-Allow-Origin"  => "*", // Required for CORS support to work
                "Access-Control-Allow-Credentials" => true, // Required for cookies, authorization headers with HTTPS
                "Content-Type" => "application/json"
            ],
            'body' => [
                'error' => $error,
                'response' => $response
            ]
        ]);
    }
}