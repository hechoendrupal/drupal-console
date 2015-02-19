<?php

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class DrupalAutoloadHelper extends Helper
{

    /**
     * @param string $drupal_root
     * @return null | string
     */
    public function findAutoload($drupal_root = false)
    {
        $currentPath = getcwd() . '/';
        $relativePath = '';
        $autoloadFound = null;

        if ($path = $this->isDrupalAutoload($drupal_root)) {
            return $path;
        }

        while (true) {
            $path = $currentPath . $relativePath;

            if ($autoloadFound = $this->isDrupalAutoload($path)) {
                return $autoloadFound;
            }
            else {
                $relativePath .= '../';
            }

            if (realpath($currentPath . $relativePath) === '/') {
                return null;
            }
        }
    }

    /**
     * @param $drupal_root
     * @return null|string
     *   Full path to drupal autoload file.
     */
    protected function isDrupalAutoload($drupal_root)
    {
        $path = realpath($drupal_root);
        $path_core_autoload = $path . '/core/vendor/autoload.php';
        $path_autoload = $path . '/vendor/autoload.php';

        if (is_dir($path)) {
            return is_file($path_core_autoload) ?
                $path_core_autoload : (is_file($path_autoload) ? $path_autoload : null);
        }
        else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'drupal-autoload';
    }

}