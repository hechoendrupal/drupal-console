<?php

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class DrupalAutoloadHelper extends Helper
{

    protected $drupalAutoLoad;

    /**
     * @param  mixed  $drupal_root
     * @return string
     */
    public function findAutoload($drupal_root = false)
    {
        $currentPath = getcwd() . '/';
        $relativePath = '';
        $autoloadFound = null;

        if ($path = $this->isDrupalAutoload($drupal_root)) {
            $this->drupalAutoLoad = $path;
            return $path;
        }

        while (true) {
            $path = $currentPath . $relativePath;

            if ($autoloadFound = $this->isDrupalAutoload($path)) {
                $this->drupalAutoLoad = $autoloadFound;
                return $autoloadFound;
            } else {
                $relativePath .= '../';
            }

            $realPath = realpath($currentPath . $relativePath);

            if ($realPath === '/' || $realPath === false) {
                return null;
            }
        }
    }

    /**
     * @param  mixed  $drupal_root
     * @return string
     */
    protected function isDrupalAutoload($drupal_root)
    {
        $path = realpath($drupal_root);
        $path_core_autoload = $path . '/core/vendor/autoload.php';
        $path_autoload = $path . '/vendor/autoload.php';

        if (is_dir($path)) {
            return is_file($path_core_autoload) ?
              $path_core_autoload : (is_file($path_autoload) ? $path_autoload : null);
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getDrupalRoot()
    {
        if (($coreIndex = stripos($this->drupalAutoLoad, 'core')) > 0) {
            return  substr($this->drupalAutoLoad, 0, $coreIndex);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'drupal-autoload';
    }
}
