<?php

/**
 * @file
 * Contains Drupal\Console\Plugin\ScriptHandler.
 */

namespace Drupal\Console\Plugin;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;

class ScriptHandler
{
    /**
     * Register
     *
     * @param \Composer\Script\Event $event
     *   The script event.
     */
    public static function dump(Event $event)
    {
        $packages = array_keys($event->getComposer()->getPackage()->getRequires());
        if (!$packages) {
            return;
        }
    }
}
