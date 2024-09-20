<?php

namespace today\revalidate\models;

use Craft;
use craft\base\Model;

/**
 * Revalidate settings
 */
class Settings extends Model
{
  /** @var string */
  public $sync = true;

  /** @var string */
  public $revalidateToken = '';

  /** @var array */
  public $revalidateHooks = [];

  /** @var string */
  public $vercelDeployHookUrl = '';

  /** @var string */
  public $vercelWebhookToken = '';

  /** @var boolean */
  public $prefetch = false;

  /** @var string */
  public $httpMethod = 'POST';
}
