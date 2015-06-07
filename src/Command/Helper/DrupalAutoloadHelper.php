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
        $path_autoload = $path . '/autoload.php';

        if (is_dir($path)) {
            return is_file($path_autoload) ? $path_autoload : null;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getDrupalRoot()
    {
        if (isset($this->drupalAutoLoad)) {
          return dirname($this->drupalAutoLoad);
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
