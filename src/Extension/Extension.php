<?php

namespace Drupal\Console\Extension;

use Drupal\Core\Extension\Extension as BaseExtension;

/**
 * Class Extension
 * @package Drupal\Console\Extension
 */
class Extension extends BaseExtension
{
    /**
     * @param bool $fullPath
     * @return string
     */
    public function getControllerDirectory($fullPath=false)
    {
        return $this->getSourcePath($fullPath) . '/Controller/';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getConfigInstallDirectory($fullPath=false)
    {
        return $this->getPath($fullPath) .'/config/install';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getConfigOptionalDirectory($fullPath=false)
    {
        return $this->getPath($fullPath) .'/config/optional';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    private function getSourcePath($fullPath)
    {
        return $this->getPath($fullPath) . '/src';
    }

    /**
     * @param $fullPath
     * @return string
     */
    public function getPath($fullPath)
    {
        if ($fullPath) {
            return $this->root . '/' . parent::getPath();
        }

        return parent::getPath();
    }

    /**
     * @param string $authenticationType
     * @param $fullPath
     * @return string
     */
    public function getAuthenticationPath($authenticationType, $fullPath = false)
    {
        return $this->getPath($fullPath) .'/src/Authentication/' . $authenticationType;
    }

    /**
     * @param string $testType
     * @param $fullPath
     * @return string
     */
    public function getTestPath( $testType, $fullPath = false)
    {
        return $this->getPath($fullPath) . '/Tests/' . $testType;
    }
}
