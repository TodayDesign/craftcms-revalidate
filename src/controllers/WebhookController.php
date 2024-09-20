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

    public function actionVercelEvent(): Response
    {
        $this->requirePostRequest();

        $settings = Craft::$app->getPlugins()->getPlugin('revalidate')->getSettings();

        $request = Craft::$app->getRequest();
        $data = json_decode($request->getRawBody(), true);
        $secretToken = $request->headers->get('X-Vercel-Signature');

        if ($secretToken !== $settings->vercelWebhookSecret) {
            throw new UnauthorizedHttpException('Invalid token');
        }

        $status = new DeploymentStatus();
        $status->type = $data['type'];
        $status->createdAt = $data['createdAt'];

        // Process the webhook data
        // You can access the data like $data['deployment']['state']

        // Log the event or update your database as needed

        if ($status->validate()) {
            Craft::$app->db->createCommand()
                ->insert('{{%revalidate_deployment_status}}', $status->toArray(['type', 'createdAt']))
                ->execute();
        }

        return $this->asJson(['success' => true]);
    }
}
