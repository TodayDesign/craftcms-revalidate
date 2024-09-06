<?php

namespace today\revalidate\jobs;

use Craft;

use craft\queue\BaseJob;
use today\revalidate\Revalidate;
use today\revalidate\services\RevalidateService;

class PrefetchTask extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    public $url = '';

    // Public Methods
    // =========================================================================
    function __construct($url) {
        $this->url = $url;
    }

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     * 
     * @param \yii\queue\Queue|QueueInterface $queue The queue the job belongs to
     */
    public function execute($queue): void
    {
        // Do work here
        $service = new RevalidateService();
        $result = $service->prefetchUrl($this->url);
    }

    /**
     *  Set time to reserve
     */
    public function getTtr()
    {
        return 3600; // One hour
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
      return "Task to prefetch a public path on the site.";
    }
}
