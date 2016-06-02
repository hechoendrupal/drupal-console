<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\File.
 */

namespace Drupal\Console\Utils;

/**
 * Class File
 * @package Drupal\Console\Utils
 */
class File
{
    /**
     * @param string $path
     * @return null|string
     */
    public function calculateRealPath($path)
    {
        if (!$path) {
            return null;
        }

        if (realpath($path)) {
            return $path;
        }

        return $this->transformToRealPath($path);
    }

    /**
     * @param $path
     * @return string
     */
    private function transformToRealPath($path)
    {
        if (strpos($path, '~') === 0) {
            $home = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/');
            $path = preg_replace('/~/', $home, $path, 1);
        }

        if (!(strpos($path, '/') === 0)) {
            $path = sprintf('%s/%s', getcwd(), $path);
        }

        return realpath($path);
    }
}
