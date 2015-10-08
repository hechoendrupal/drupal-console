<?php

/**
 * @file
 * Contains Drupal\Console\Helper\DrupalHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;

/**
 * Class DrupalHelper
 * @package Drupal\Console\Helper
 */
class DrupalHelper extends Helper
{
    const DRUPAL_AUTOLOAD = 'autoload.php';

    const DRUPAL_SETTINGS = 'sites/default/settings.php';

    /**
     * @var string
     */
    private $root = false;

    /**
     * @var string
     */
    private $autoLoad = null;

    /**
     * @var bool
     */
    private $installed = false;

    /**
     * @param  string $root
     * @param  bool   $recursive
     * @return bool
     */
    public function isValidRoot($root, $recursive=false)
    {
        if (!$root) {
            return false;
        }

        if ($root === '/') {
            return false;
        }

        $autoLoad = sprintf('%s/%s', $root, self::DRUPAL_AUTOLOAD);

        if (file_exists($autoLoad)) {
            $this->root = $root;
            $this->autoLoad = $autoLoad;
            $this->installed = $this->isSettingsFile();
            return true;
        }

        if ($recursive) {
            return $this->isValidRoot(realpath($root . '/../'), $recursive);
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isSettingsFile()
    {
        $settingsPath = sprintf('%s/%s', $this->root, self::DRUPAL_SETTINGS);

        return file_exists($settingsPath);
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getAutoLoad()
    {
        return $this->autoLoad;
    }

    /**
     * @return string
     */
    public function getAutoLoadClass()
    {
        return include $this->autoLoad;
    }

    /**
     * @return bool
     */
    public function isAutoload()
    {
        return ($this->autoLoad?true:false);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drupal';
    }
}
