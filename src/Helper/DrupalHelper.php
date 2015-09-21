<?php

/**
 * @file
 * Contains Drupal\AppConsole\Helper\DrupalHelper.
 */

namespace Drupal\AppConsole\Helper;

use Symfony\Component\Console\Helper\Helper;

/**
 * Class DrupalHelper
 * @package Drupal\AppConsole\Helper
 */
class DrupalHelper extends Helper
{
    const DRUPAL_AUTOLOAD = 'core/vendor/autoload.php';

    const DRUPAL_SETTINGS = 'sites/default/settings.php';

    /**
     * @var string
     */
    private $drupalRoot;

    /**
     * @var string
     */
    private $drupalAutoLoadPath;

    /**
     * @var bool
     */
    private $bootable;

    /**
     * @param  string $drupalRoot
     * @return bool
     */
    public function isValidInstance($drupalRoot=null)
    {
        if ($drupalRoot) {
            return $this->isValidRoot($drupalRoot);
        }

        $drupalRoot = getcwd();

        return $this->isAutoLoader($drupalRoot);
    }

    /**
     * @param $drupalRoot
     * @return bool
     */
    private function isAutoLoader($drupalRoot)
    {
        if ($drupalRoot === '/') {
            return false;
        }

        if ($this->isValidRoot($drupalRoot)) {
            return true;
        }

        return $this->isAutoLoader(realpath($drupalRoot . '/../'));
    }

    /**
     * @param  string $drupalRoot
     * @return bool
     */
    private function isValidRoot($drupalRoot)
    {
        if (!$drupalRoot) {
            return false;
        }

        $drupalAutoLoadPath = sprintf('%s/%s', $drupalRoot, self::DRUPAL_AUTOLOAD);

        if (file_exists($drupalAutoLoadPath)) {
            $this->drupalRoot = $drupalRoot;
            $this->drupalAutoLoadPath = $drupalAutoLoadPath;
            $this->bootable = true;
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isSettingsFile()
    {
        $drupalSettingsPath = sprintf('%s/%s', $this->drupalRoot, self::DRUPAL_SETTINGS);

        if (!file_exists($drupalSettingsPath)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        if (!$this->isBootable()) {
            return false;
        }

        if (!$this->isSettingsFile()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isBootable()
    {
        return $this->bootable;
    }

    /**
     * @return string
     */
    public function getDrupalRoot()
    {
        return $this->drupalRoot;
    }

    /**
     * @return string
     */
    public function getDrupalAutoLoadPath()
    {
        return $this->drupalAutoLoadPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drupal';
    }
}
