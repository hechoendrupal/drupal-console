<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Helper\SiteHelper
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Drupal\AppConsole\Utils\DrupalExtensionDiscovery;

/**
 * Class SiteHelper
 * @package Drupal\AppConsole\Command\Helper
 */

class SiteHelper extends Helper
{
    /**
     * @var string
     */
    private $modulePath;

    /**
     * @param string $modulePath
     */
    public function setModulePath($modulePath)
    {
        $this->modulePath = $modulePath;
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getModulePath($moduleName)
    {
        if (!$this->modulePath) {
            /*
            * @todo Remove DrupalExtensionDiscovery subclass once
            * https://www.drupal.org/node/2503927 is fixed.
            */
            $discovery = new DrupalExtensionDiscovery(\Drupal::root());
            $discovery->reset();
            $result = $discovery->scan('module');
            $this->modulePath = DRUPAL_ROOT.'/'.$result[$moduleName]->getPath();
        }

        return $this->modulePath;
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getControllerPath($moduleName)
    {
        return $this->getModulePath($moduleName).'/src/Controller';
    }

    /**
     * @param string $moduleName
     * @param string $testType
     * @return string
     */
    public function getTestPath($moduleName, $testType)
    {
        return $this->getModulePath($moduleName).'/Tests/'.$testType;
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getFormPath($moduleName)
    {
        return $this->getModulePath($moduleName).'/src/Form';
    }

    /**
     * @param string $moduleName
     * @param string $pluginType
     * @return string
     */
    public function getPluginPath($moduleName, $pluginType)
    {
        return $this->getModulePath($moduleName).'/src/Plugin/'.$pluginType;
    }

    /**
     * @param string $moduleName
     * @param string $authenticationType
     * @return string
     */
    public function getAuthenticationPath($moduleName, $authenticationType)
    {
        return $this->getModulePath($moduleName).'/src/Authentication/'.$authenticationType;
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getCommandPath($moduleName)
    {
        return $this->getModulePath($moduleName).'/src/Command';
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getSourcePath($moduleName)
    {
        return $this->getModulePath($moduleName).'/src';
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function getEntityPath($moduleName)
    {
        return $this->getModulePath($moduleName).'/src/Entity';
    }

    /**
     * @param string $moduleName
     *
     * @return string
     */
    public function getTemplatePath($moduleName)
    {
        return $this->getModulePath($moduleName).'/templates';
    }

    /**
     * @param string $moduleName
     *
     * @return string
     */
    public function getTranslationsPath($moduleName)
    {
        return $this->getModulePath($moduleName).'/config/translations';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'site';
    }
}
