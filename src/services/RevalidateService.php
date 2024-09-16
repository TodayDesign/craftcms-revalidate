<?php

namespace today\revalidate\services;

use Craft;
use craft\base\Component;
use revalidate\Revalidate;
use craft\events\ElementEvent;
use craft\helpers\ElementHelper;
use craft\elements\Entry;
use GuzzleHttp\Client;
use craft\helpers\Json;
use today\revalidate\jobs\PrefetchTask;

class RevalidateService extends Component
{
  public function revalidateElement($element) {
    // If is draft or revision, don't revalidate
    if (ElementHelper::isDraftOrRevision($element)) {
      return;
    }

    // Get settings from plugin
    $settings = $this->getSettings();
    $tags = [];
    $paths = [];
    $siteUrl = [];

    // Get GraphQL type
    $graphqlType = $element->getGqlTypeName();

    // If element has `uri` property, use that
    if (isset($element->uri)) {
      $paths[] = $element->uri === '__home__' ? '/' : ('/' . $element->uri);
      $tags[] = $element->uri;

      // if uri includes a slash get the parent uri by removing the last segment
      if (strpos($element->uri, '/') !== false) {
        $parentUri = substr($element->uri, 0, strrpos($element->uri, '/'));
        $tags[] = $parentUri;
        $paths[] = $parentUri === '__home__' ? '/' : ('/' . $parentUri);
      }
    }

    // Get site URL from element
    $siteUrl = $element->site->getBaseUrl();

    if (!$siteUrl) {
      $siteUrl = Craft::$app->sites->currentSite->getBaseUrl();
    }

    // Check if there is a matching hook in settings
    foreach ($settings->revalidateHooks as $key=>$value) {
      if ($key === $graphqlType) {
        // Check to see if there is a property that matches the section handle
        if (isset($value[$element->section->handle])) {
          $sectionConfig = $value[$element->section->handle];

           // Check for `tags` property
          if (isset($sectionConfig['tags'])) {
            // If is array, add to tags
            if (is_array($sectionConfig['tags'])) {
              $tags = array_merge($tags, $sectionConfig['tags']);
            } else {
              $tags[] = $sectionConfig['tags'];
            }
          }

          // Check for `paths` property
          if (isset($sectionConfig['paths'])) {
            // If is array, add to paths
            if (is_array($sectionConfig['paths'])) {
              $paths = array_merge($paths, $sectionConfig['paths']);
            } else {
              $paths[] = $sectionConfig['paths'];
            }
          }
        }

        // Check for `tags` property
        if (isset($value['tags'])) {
          // If is array, add to tags
          if (is_array($value['tags'])) {
            $tags = array_merge($tags, $value['tags']);
          } else {
            $tags[] = $value['tags'];
          }
        }

        // Check for `paths` property
        if (isset($value['paths'])) {
          // If is array, add to paths
          if (is_array($value['paths'])) {
            $paths = array_merge($paths, $value['paths']);
          } else {
            $paths[] = $value['paths'];
          }
        }
      }
    }

    // Revalidate paths and tags if they exist
    if (count($paths) > 0 || count($tags) > 0) {
      $this->revalidate($siteUrl, [ 'paths' => $paths, 'tags' => $tags ]);

      if ($settings->prefetch) {
        // Deduplicate paths
        $paths = array_unique($paths);

        foreach ($paths as $path) {
          $url = $siteUrl . $path;

          // Remove any double slashes
          $url = preg_replace('#([^:])//+#', '$1/', $url);

          $task = new PrefetchTask($url);

          Craft::$app->queue->ttr(3600);
          Craft::$app->queue->priority(1024);
          Craft::$app->queue->push($task);
        }
      }
    }
  }

  public function revalidateAll() {
    $this->revalidate(Craft::$app->sites->currentSite->getBaseUrl(), [ 'paths' => ['/*'], 'tags' => ['site-data']]);
  }

  public function revalidateSiteData() {
    $this->revalidate(Craft::$app->sites->currentSite->getBaseUrl(), [  'paths' => [], 'tags' => ['site-data']]);
  }

  public function deploy() {
    try {
      // Rebuild app in Vercel
      $client = new Client();
      $settings = $this->getSettings();

      if (!$settings->vercelDeployHookUrl) {
        throw new \Exception('Vercel deploy hook URL not set');
      }

      $response = $client->request('GET', $settings->vercelDeployHookUrl);

      if ($response->getStatusCode() == 201) {
        $body = $response->getBody()->getContents();

        // Convert to JSON
        $json = Json::decode($body);

        // Check if there are any errors
        if (isset($json['errors'])) {
          throw new \Exception($json['errors'][0]['message']);
        }

        // Revalidate successful
        $this->setSessionNotice('New build started, should be live in a few minutes');
      } else {
        throw new \Exception('Revalidate failed');
      }
    } catch (\Exception $e) {
      $this->setSessionError($e->getMessage());
    }
  }

  public function revalidate($siteUrl = '', $query = [ 'paths' => [], 'tags' => [] ]) {
    try {
      $settings = $this->getSettings();
      $client = new Client();
      $key = $settings->httpMethod === 'GET' ? 'query' : 'form_params';

      $params = [
        $key => [
          'secret' => $settings->revalidateToken,
          'paths' => join(',', $query['paths']),
          'tags' => join(',', $query['tags'])
        ],
      ];

      // If `siteUrl` contains `localhost`, use `host.docker.internal` instead
      if (strpos($siteUrl, 'localhost') !== false) {
        $siteUrl = str_replace('localhost', 'host.docker.internal', $siteUrl);
      }

      $response = $client->request($settings->httpMethod, $siteUrl . 'api/revalidate', $params);

      if ($response->getStatusCode() == 200) {
        $body = $response->getBody()->getContents();

        // Convert to JSON
        $json = Json::decode($body);

        // Check if there are any errors
        if (isset($json['errors'])) {
          throw new \Exception($json['errors'][0]['message']);
        }

        // Revalidate successful
        $this->setSessionNotice('Revalidate successful');
      } else {
        throw new \Exception('Revalidate failed');
      }
    } catch (\Exception $e) {
      $this->setSessionError($e->getMessage());
    }
  }

  public function isUpdatedElement($element): bool
  {
      return
          !$element->firstSave &&
          !ElementHelper::isDraftOrRevision($element) &&
          !$element->resaving;
  }

  private function getSettings() {
    return Craft::$app->getPlugins()->getPlugin('revalidate')->getSettings();
  }

  private function setSessionNotice($message) {
    if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
      Craft::$app->getSession()->setNotice($message);
    }
  }

  private function setSessionError($message) {
    if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
      Craft::$app->getSession()->setError($message);
    }
  }

  public function prefetchUrl($url) {
    // If `siteUrl` contains `localhost`, use `host.docker.internal` instead
    if (strpos($url, 'localhost') !== false) {
      $url = str_replace('localhost', 'host.docker.internal', $url);
    }

    $client = new Client();
    $response = $client->request('GET', $url);

    if ($response->getStatusCode() == 200) {
      // Log success
      Craft::info('Prefetch successful', 'revalidate');
    } else {
      // Log error
      Craft::error('Prefetch failed', 'revalidate');
    }
  }
}
