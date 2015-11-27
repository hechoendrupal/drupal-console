<?php

/**
 * @file
 * Contains \Drupal\Console\Helper\SiteHelper
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Helper\Helper;
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
    private $siteRoot;

    /**
     * @return string
     */
    public function getSiteRoot()
    {
        return $this->siteRoot;
    }

    /**
     * @param string $siteRoot
     */
    public function setSiteRoot($siteRoot)
    {
        $this->siteRoot = $siteRoot;
    }

    /**
     * @return \Drupal\Core\Extension\Extension[]
     */
    private function discoverModules()
    {
        /*
         * @see Remove DrupalExtensionDiscovery subclass once
         * https://www.drupal.org/node/2503927 is fixed.
         */
        $discovery = new DrupalExtensionDiscovery(\Drupal::root());
        $discovery->reset();

        return $discovery->scan('module');
    }

    /**
     * @return \Drupal\Core\Extension\Extension[]
     */
    private function discoverThemes()
    {
        /*
         * @see Remove DrupalExtensionDiscovery subclass once
         * https://www.drupal.org/node/2503927 is fixed.
         */
        $discovery = new DrupalExtensionDiscovery(\Drupal::root());
        $discovery->reset();

        return $discovery->scan('theme');
    }

    /**
     * @return array
     */
    private function getInstalledModules()
    {
        $kernel = $this->getKernelHelper()->getKernel();
        if (!$kernel) {
            return [];
        }
        $container = $kernel->getContainer();
        if (!$container) {
            return [];
        }
        $configFactory = $container->get('config.factory');
        if (!$configFactory) {
            return [];
        }
        $coreExtension = $configFactory->get('core.extension');
        if (!$coreExtension) {
            return [];
        }
        return $coreExtension->get('module') ?: [];
    }
    
    /**
     * @return array
     */
    private function getInstalledThemes()
    {
        $kernel = $this->getKernelHelper()->getKernel();
        if (!$kernel) {
            return [];
        }
        $container = $kernel->getContainer();
        if (!$container) {
            return [];
        }
        $configFactory = $container->get('config.factory');
        if (!$configFactory) {
            return [];
        }
        $coreExtension = $configFactory->get('core.extension');
        if (!$coreExtension) {
            return [];
        }
        return $coreExtension->get('theme') ?: [];
    }

    /**
     * @param bool|false $reset
     * @param bool|false $installedOnly
     * @param bool|true  $showCore
     * @param bool|true  $showNoCore
     * @param bool|false $nameOnly
     * @return array
     */
    public function getModules(
        $reset = false,
        $installedOnly = false,
        $showCore = true,
        $showNoCore = true,
        $nameOnly = false
    ) {
        $installedModules = $this->getInstalledModules();
        $modules = [];

        if (!$this->modules || $reset) {
            $this->modules = $this->discoverModules();
        }

        foreach ($this->modules as $module) {
            $name = $module->getName();
            if ($installedOnly && !array_key_exists($name, $installedModules)) {
                continue;
            }
            if (!$showCore && $module->origin == 'core') {
                continue;
            }
            if (!$showNoCore && $module->origin != 'core') {
                continue;
            }
            if ($nameOnly) {
                $modules[] = $name;
            } else {
                $modules[$name] = $module;
            }
        }

        return $modules;
    }
    
    /**
     * @param bool|false $reset
     * @param bool|false $installedOnly
     * @param bool|false $nameOnly
     * @return array
     */
    public function getThemes(
        $reset = false,
        $installedOnly = false,
        $nameOnly = false
    ) {
        $installedThemes = $this->getInstalledThemes();
        $themes = [];

        if (!$this->themes || $reset) {
            $this->themes = $this->discoverThemes();
        }

        foreach ($this->themes as $theme) {
            $name = $theme->getName();
            if ($installedOnly && !array_key_exists($name, $installedThemes)) {
                continue;
            }
            if ($nameOnly) {
                $themes[] = $name;
            } else {
                $themes[$name] = $theme;
            }
        }

        return $themes;
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
            $this->siteRoot,
            $this->modules[$moduleName]->getPath()
        );

        if (!$fullPath) {
            $modulePath = str_replace(
                sprintf(
                    '%s/',
                    $this->siteRoot
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
     * @param bool   $fullPath
     * @return string
     */
    public function getModuleInfoFile($moduleName, $fullPath=true)
    {
        return $this->getModulePath($moduleName, $fullPath)."/$moduleName.info.yml";
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
     * @param string $moduleName
     * @return string
     */
    public function getRoutingPath($moduleName)
    {
        return $this->getModulePath($moduleName).'/src/Routing';
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'site';
    }
}
