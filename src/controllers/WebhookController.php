<?php
namespace today\revalidate\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use today\revalidate\models\DeploymentStatus;

class WebhookController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public function beforeAction($action): bool
    {

        // if ($action->id === 'vercel') {
        //     $this->enableCsrfValidation = false;
        // }
        $this->enableCsrfValidation = false;
        
        return parent::beforeAction($action);
    }


    public function actionVercel(): Response
    {
        $this->requirePostRequest();

        $settings = Craft::$app->getPlugins()->getPlugin('revalidate')->getSettings();

        $request = Craft::$app->getRequest();
        $rawBody = $request->getRawBody();
        $data = json_decode($rawBody, true);
        $receivedSignature = $request->headers->get('x-vercel-signature');
        // Compute the expected signature
        $computedSignature = hash_hmac('sha256', $rawBody, $settings->vercelWebhookToken);

        // Compare signatures
        if (!hash_equals($computedSignature, $receivedSignature)) {
            Craft::error('Signature mismatch. Computed: ' . $computedSignature . ', Received: ' . $receivedSignature, 'revalidate');
            throw new UnauthorizedHttpException('Invalid signature');
        }



        // if ($secretToken !== $settings->vercelWebhookToken) {
        //     throw new UnauthorizedHttpException('Invalid token');
        // }

        $status = new DeploymentStatus();
        $status->type = $data['type'];

        // Convert Vercel's millisecond timestamp to a proper datetime format
        if (isset($data['createdAt'])) {
            // Convert milliseconds to seconds and format as datetime
            $timestamp = (int)($data['createdAt'] / 1000);
            $status->createdAt = date('Y-m-d H:i:s', $timestamp);
        }



        if ($status->validate()) {
            Craft::$app->db->createCommand()
                ->insert('{{%revalidate_deployment_status}}', $status->toArray(['type', 'createdAt']))
                ->execute();
        }

        return $this->asJson(['success' => true]);
    }
}
