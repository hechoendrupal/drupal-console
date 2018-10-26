<?php

namespace Drupal\Console\Extension;

use Drupal\Core\Extension\Extension as BaseExtension;

/**
 * Class Extension
 *
 * @package Drupal\Console\Extension
 */
class Extension extends BaseExtension
{
    /**
     * @param $fullPath
     * @return string
     */
    public function getPath($fullPath = false)
    {
        if ($fullPath) {
            return $this->root . '/' . parent::getPath();
        }

        return parent::getPath();
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getControllerPath($fullPath = false)
    {
        return $this->getSourcePath($fullPath) . '/Controller';
    }

     /**
     * @param bool $fullPath
     * @return string
     */
    public function getAjaxPath($fullPath = false)
    {
        return $this->getSourcePath($fullPath) . '/Ajax';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getConfigInstallDirectory($fullPath = false)
    {
        return $this->getPath($fullPath) .'/config/install';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getConfigOptionalDirectory($fullPath = false)
    {
        return $this->getPath($fullPath) .'/config/optional';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getSourcePath($fullPath=false)
    {
        return $this->getPath($fullPath) . '/src';
    }

    /**
     * @param string  $authenticationType
     * @param boolean $fullPath
     * @return string
     */
    public function getAuthenticationPath($authenticationType, $fullPath = false)
    {
        return $this->getSourcePath($fullPath) .'/Authentication/' . $authenticationType;
    }

    /**
     * @param $fullPath
     * @return string
     */
    public function getFormPath($fullPath = false)
    {
        return $this->getSourcePath($fullPath) . '/Form';
    }

    /**
     * @param $fullPath
     * @return string
     */
    public function getRoutingPath($fullPath = false)
    {
        return $this->getSourcePath($fullPath) . '/Routing';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getCommandDirectory($fullPath=false)
    {
        return $this->getSourcePath($fullPath) . '/Command/';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getGeneratorDirectory($fullPath=false)
    {
        return $this->getSourcePath($fullPath) . '/Generator/';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getEntityPath($fullPath = false)
    {
        return $this->getSourcePath($fullPath) . '/Entity';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getTemplatePath($fullPath = false)
    {
        return $this->getPath($fullPath) . '/templates';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getTestsPath($fullPath = false)
    {
        return $this->getPath($fullPath) . '/tests';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getTestsSourcePath($fullPath = false)
    {
        return $this->getTestsPath($fullPath) . '/src';
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getJsTestsPath($fullPath = false)
    {
        return $this->getTestsSourcePath($fullPath) . '/FunctionalJavascript';
    }
}
