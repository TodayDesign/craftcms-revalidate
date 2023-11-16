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

    // Check if there is a matching hook in settings
    foreach ($settings->revalidateHooks as $key=>$value) {
      if ($key === $graphqlType) {
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

      // If element as URL, prefetch it
      if (isset($element->url) && $settings->prefetch) {
        $this->prefetchUrl($element->url);
      }
    }
  }

  public function revalidateAll() {
    $this->revalidate(Craft::$app->sites->currentSite->getBaseUrl(), [ 'paths' => ['/[[...uri]]'], 'tags' => ['site-data']]);
  }

  public function revalidateRedirects() {
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
        Craft::$app->getSession()->setNotice('New build started, should be live in a few minutes');
      } else {
        throw new \Exception('Revalidate failed');
      }
    } catch (\Exception $e) {
      Craft::$app->getSession()->setError($e->getMessage());
    }
  }

  public function revalidate($siteUrl = '', $query = [ 'paths' => [], 'tags' => [] ]) {
    try {
      $settings = $this->getSettings();
      $client = new Client();
      $params = [
        'query' => [ 
          'secret' => $settings->revalidateToken,
          'paths' => join(',', $query['paths']),
          'tags' => join(',', $query['tags'])
        ],
      ];

      // If `siteUrl` contains `localhost`, use `host.docker.internal` instead
      if (strpos($siteUrl, 'localhost') !== false) {
        $siteUrl = str_replace('localhost', 'host.docker.internal', $siteUrl);
      }

      $response = $client->request('GET', $siteUrl . 'api/revalidate', $params);

      if ($response->getStatusCode() == 200) {
        $body = $response->getBody()->getContents();

        // Convert to JSON
        $json = Json::decode($body);

        // Check if there are any errors
        if (isset($json['errors'])) {
          throw new \Exception($json['errors'][0]['message']);
        }

        // Revalidate successful
        Craft::$app->getSession()->setNotice('Revalidate successful');
      } else {
        throw new \Exception('Revalidate failed');
      }
    } catch (\Exception $e) {
      Craft::$app->getSession()->setError($e->getMessage());
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

  public function prefetchUrl($url) {
    try {
      // If `siteUrl` contains `localhost`, use `host.docker.internal` instead
      if (strpos($url, 'localhost') !== false) {
        $url = str_replace('localhost', 'host.docker.internal', $url);
      }

      $client = new Client();
      $response = $client->request('GET', $url);

      if ($response->getStatusCode() == 200) {
        // Revalidate successful
      } else {
        throw new \Exception('Prefetch failed');
      }
    } catch (\Exception $e) {
      Craft::$app->getSession()->setError($e->getMessage());
    }
  }
}
