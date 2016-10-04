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
    private $extensions;

    /**
     * @var array
     */
    private $modules;

    /**
     * @var array
     */
    private $themes;

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
     * @param string $type
     * @return \Drupal\Core\Extension\Extension[]
     */
    public function discoverExtensions($type = 'module')
    {
        $this->getDrupalHelper()->loadLegacyFile('/core/modules/system/system.module');
        system_rebuild_module_data();

        /*
         * @see Remove DrupalExtensionDiscovery subclass once
         * https://www.drupal.org/node/2503927 is fixed.
         */
        $discovery = new DrupalExtensionDiscovery(\Drupal::root());
        $discovery->reset();

        return $discovery->scan($type);
    }

    /**
     * @return \Drupal\Core\Extension\Extension[]
     */
    public function discoverModules()
    {
        return $this->discoverExtensions();
    }

    /**
     * @return \Drupal\Core\Extension\Extension[]
     */
    public function discoverProfiles()
    {
        return $this->discoverExtensions('profile');
    }

    /**
     * @param string     $type
     * @param bool|false $reset
     * @param bool|true  $showInstalled
     * @param bool|false $showUninstalled
     * @param bool|true  $showCore
     * @param bool|true  $showNoCore
     * @param bool|false $nameOnly
     * @return array
     */
    public function getExtensions(
        $type = 'module',
        $reset = false,
        $showInstalled = true,
        $showUninstalled = false,
        $showCore = true,
        $showNoCore = true,
        $nameOnly = false
    ) {
        $extensions = [];

        if (!$this->extensions[$type] || $reset) {
            $this->extensions[$type] = $this->discoverExtensions($type);
        }

        foreach ($this->extensions[$type] as $extension) {
            $name = $extension->getName();

            $isInstalled = false;
            if (property_exists($extension, 'status')) {
                $isInstalled = ($extension->status)?true:false;
            }
            if (!$showInstalled && $isInstalled) {
                continue;
            }
            if (!$showUninstalled && !$isInstalled) {
                continue;
            }
            if (!$showCore && $extension->origin == 'core') {
                continue;
            }
            if (!$showNoCore && $extension->origin != 'core') {
                continue;
            }
            if ($nameOnly) {
                $extensions[] = $name;
            } else {
                $extensions[$name] = $extension;
            }
        }

        return $extensions;
    }
    
    /**
     * @param bool|false $reset
     * @param bool|true  $showInstalled
     * @param bool|false $showUninstalled
     * @param bool|true  $showCore
     * @param bool|true  $showNoCore
     * @param bool|false $nameOnly
     * @return array
     */
    public function getModules(
        $reset = false,
        $showInstalled = true,
        $showUninstalled = false,
        $showCore = true,
        $showNoCore = true,
        $nameOnly = false
    ) {
        return $this->getExtensions('module', $reset, $showInstalled, $showUninstalled, $showCore, $showNoCore, $nameOnly);
    }

    /**
     * @param bool|false $reset
     * @param bool|true  $showInstalled
     * @param bool|false $showUninstalled
     * @param bool|true  $showCore
     * @param bool|true  $showNoCore
     * @param bool|false $nameOnly
     * @return array
     */
    public function getProfiles(
        $reset = false,
        $showInstalled = true,
        $showUninstalled = false,
        $showCore = true,
        $showNoCore = true,
        $nameOnly = false
    ) {
        return $this->getExtensions('profile', $reset, $showInstalled, $showUninstalled, $showCore, $showNoCore, $nameOnly);
    }

    /**
     * @param bool|false $reset
     * @param bool|false $nameOnly
     * @return \Drupal\Core\Extension\Extension The currently enabled profile.
     */
    public function getProfile(
        $reset = false,
        $nameOnly = false
    ) {
        $profiles = $this->getProfiles($reset, true, false, true, true, $nameOnly);
        return reset($profiles);
    }

    /**
     * @param bool|false $reset
     * @param bool|false $showInstalled
     * @param bool|false $showUninstalled
     * @param bool|false $nameOnly
     * @return array
     */
    public function getThemes(
        $reset = false,
        $showInstalled = true,
        $showUninstalled = false,
        $nameOnly = false
    ) {
        $themes = [];

        if (!$this->themes || $reset) {
            $this->themes = $this->getDrupalApi()->getService('theme_handler')->rebuildThemeData();
        }

        foreach ($this->themes as $theme) {
            $name = $theme->getName();

            $isInstalled = false;
            if (property_exists($theme, 'status')) {
                $isInstalled = ($theme->status)?true:false;
            }
            if (!$showInstalled && $isInstalled) {
                continue;
            }
            if (!$showUninstalled && !$isInstalled) {
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

        // Profiles are also modules. If the module is not found, try profiles.
        if (empty($this->modules[$moduleName])) {
            $this->modules = $this->discoverProfiles();
        }

        if (array_key_exists($moduleName, $this->modules)) {
            $modulePath = sprintf(
                '%s/%s',
                $this->siteRoot,
                $this->modules[$moduleName]->getPath()
            );
        }

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
     * @param string $themeName
     * @param bool   $fullPath
     * @return string
     */
    public function getThemePath($themeName, $fullPath=true)
    {
        if (!$this->themes || !$this->themes[$themeName]) {
            $this->themes = $this->discoverExtensions('theme');
        }

        $themePath = sprintf(
            '%s/%s',
            $this->siteRoot,
            $this->themes[$themeName]->getPath()
        );

        if (!$fullPath) {
            $themePath = str_replace(
                sprintf(
                    '%s/',
                    $this->siteRoot
                ),
                '',
                $themePath
            );
        }

        return $themePath;
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
     * @return string
     */
    public function getDrupalVersion()
    {
        return \Drupal::VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'site';
    }
}
