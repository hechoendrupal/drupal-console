<?php

/**
 * @file
 * Contains \Drupal\Console\Helper\SiteHelper
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Drupal\Console\Utils\DrupalExtensionDiscovery;

/**
 * Class SiteHelper
 * @package Drupal\Console\Command\Helper
 */

class SiteHelper extends Helper
{
    /**
     * @var array
     */
    private $modules;

    /**
     * @var string
     */
    private $sitePath;

    /**
     * @return string
     */
    public function getSitePath()
    {
        return $this->sitePath;
    }

    /**
     * @param string $sitePath
     */
    public function setSitePath($sitePath)
    {
        $this->sitePath = $sitePath;
    }

    /**
     * @return \Drupal\Core\Extension\Extension[]
     */
    private function discoverModules()
    {
        /*
         * @todo Remove DrupalExtensionDiscovery subclass once
         * https://www.drupal.org/node/2503927 is fixed.
         */
        $discovery = new DrupalExtensionDiscovery(\Drupal::root());
        $discovery->reset();

        return $discovery->scan('module');
    }

    /**
     * @param string $moduleName
     * @param bool   $fullPath
     * @return string
     */
    public function getModulePath($moduleName, $fullPath=true)
    {
        if (!$this->modules || !$this->modules[$moduleName]) {
            $this->modules = $this->discoverModules();
        }

        $modulePath = sprintf(
            '%s/%s',
            $this->sitePath,
            $this->modules[$moduleName]->getPath()
        );

        if (!$fullPath) {
            $modulePath = str_replace(
                sprintf(
                    '%s/',
                    $this->sitePath
                ),
                '',
                $modulePath
            );
        }

        return $modulePath;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function createModuleConfigDirectory($moduleName)
    {
        if (!$moduleName) {
            return false;
        }

        $modulePath = $this->getModulePath($moduleName);

        if (!file_exists($modulePath .'/config')) {
            mkdir($modulePath .'/config', 0755, true);
        }

        return true;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function createModuleConfigInstallDirectory($moduleName)
    {
        if (!$moduleName) {
            return false;
        }

        if (!$this->createModuleConfigDirectory($moduleName)) {
            return false;
        }

        $modulePath = $this->getModulePath($moduleName);

        if (!file_exists($modulePath .'/config/install')) {
            mkdir($modulePath .'/config/install', 0755, true);
        }

        return true;
    }

    /**
     * @param string $moduleName
     * @param bool   $fullPath
     * @return string
     */
    public function getModuleConfigInstallDirectory($moduleName, $fullPath=true)
    {
        return $this->getModulePath($moduleName, $fullPath).'/config/install';
    }

    /**
     * @param string $moduleName
     * @param bool   $fullPath
     * @return string
     */
    public function getModuleConfigOptionalDirectory($moduleName, $fullPath=true)
    {
        return $this->getModulePath($moduleName, $fullPath).'/config/optional';
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
