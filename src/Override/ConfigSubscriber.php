<?php

namespace Drupal\Console\Override;

use Drupal\system\SystemConfigSubscriber;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigEvents;

/**
 * Class ConfigSubscriber
 *
 * @package Drupal\Console\Override
 */
class ConfigSubscriber extends SystemConfigSubscriber
{

    /**
     * @param \Drupal\Core\Config\ConfigImporterEvent $event
     * @return bool
     */
    public function onConfigImporterValidateSiteUUID(ConfigImporterEvent $event)
    {
        $event->stopPropagation();
        return true;
    }
}
