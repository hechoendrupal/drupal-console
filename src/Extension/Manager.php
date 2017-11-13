<?php

namespace Drupal\Console\Extension;

use Drupal\Console\Utils\Site;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class ExtensionManager
 *
 * @package Drupal\Console
 */
class Manager
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var array
     */
    private $extensions = [];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var string
     */
    private $extension = null;

    /**
     * ExtensionManager constructor.
     *
     * @param Site   $site
     * @param Client $httpClient
     * @param string $appRoot
     */
    public function __construct(
        Site $site,
        Client $httpClient,
        $appRoot
    ) {
        $this->site = $site;
        $this->httpClient = $httpClient;
        $this->appRoot = $appRoot;
        $this->initialize();
    }

    /**
     * @return $this
     */
    public function showInstalled()
    {
        $this->filters['showInstalled'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showUninstalled()
    {
        $this->filters['showUninstalled'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showCore()
    {
        $this->filters['showCore'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showNoCore()
    {
        $this->filters['showNoCore'] = true;
        return $this;
    }

    /**
     * @param boolean $nameOnly
     * @return array
     */
    public function getList($nameOnly = false)
    {
        return $this->getExtensions($this->extension, $nameOnly);
    }

    /**
     * @return $this
     */
    public function discoverModules()
    {
        $this->initialize();
        $this->discoverExtension('module');

        return $this;
    }

    /**
     * @return $this
     */
    public function discoverThemes()
    {
        $this->initialize();
        $this->discoverExtension('theme');

        return $this;
    }

    /**
     * @return $this
     */
    public function discoverProfiles()
    {
        $this->initialize();
        $this->discoverExtension('profile');

        return $this;
    }

    /**
     * @param string $extension
     */
    private function discoverExtension($extension)
    {
        $this->extension = $extension;
        $this->extensions[$extension] = $this->discoverExtensions($extension);

        return $this;
    }

    /**
     * initializeFilters
     */
    private function initialize()
    {
        $this->extension = 'module';
        $this->extensions = [
            'module' => [],
            'theme' => [],
            'profile' => [],
        ];
        $this->filters = [
            'showInstalled' => false,
            'showUninstalled' => false,
            'showCore' => false,
            'showNoCore' => false
        ];
    }

    /**
     * @param string     $type
     * @param bool|false $nameOnly
     * @return array
     */
    private function getExtensions(
        $type = 'module',
        $nameOnly = false
    ) {
        $showInstalled = $this->filters['showInstalled'];
        $showUninstalled = $this->filters['showUninstalled'];
        $showCore = $this->filters['showCore'];
        $showNoCore = $this->filters['showNoCore'];

        $extensions = [];
        if (!array_key_exists($type, $this->extensions)) {
            return $extensions;
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

            $extensions[$name] = $extension;
        }


        return $nameOnly?array_keys($extensions):$extensions;
    }

    /**
     * @param string $type
     * @return \Drupal\Core\Extension\Extension[]
     */
    private function discoverExtensions($type)
    {
        if ($type === 'module') {
            $this->site->loadLegacyFile('/core/modules/system/system.module');
            system_rebuild_module_data();
        }

        if ($type === 'theme') {
            $themeHandler = \Drupal::service('theme_handler');
            $themeHandler->rebuildThemeData();
        }

        /*
         * @see Remove DrupalExtensionDiscovery subclass once
         * https://www.drupal.org/node/2503927 is fixed.
         */
        $discovery = new Discovery($this->appRoot);
        $discovery->reset();

        return $discovery->scan($type);
    }

    /**
     * @param string $name
     * @return \Drupal\Console\Extension\Extension
     */
    public function getModule($name)
    {
        if ($extension = $this->getExtension('module', $name)) {
            return $this->createExtension($extension);
        }

        return null;
    }

    /**
     * @param string $name
     * @return \Drupal\Console\Extension\Extension
     */
    public function getProfile($name)
    {
        if ($extension = $this->getExtension('profile', $name)) {
            return $this->createExtension($extension);
        }

        return null;
    }

    /**
     * @param string $name
     * @return \Drupal\Console\Extension\Extension
     */
    public function getTheme($name)
    {
        if ($extension = $this->getExtension('theme', $name)) {
            return $this->createExtension($extension);
        }

        return null;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return \Drupal\Core\Extension\Extension
     */
    private function getExtension($type, $name)
    {
        if (!$this->extensions[$type]) {
            $this->discoverExtension($type);
        }

        if (array_key_exists($name, $this->extensions[$type])) {
            return $this->extensions[$type][$name];
        }

        return null;
    }

    /**
     * @param \Drupal\Core\Extension\Extension $extension
     * @return \Drupal\Console\Extension\Extension
     */
    private function createExtension($extension)
    {
        $consoleExtension = new Extension(
            $this->appRoot,
            $extension->getType(),
            $extension->getPathname(),
            $extension->getExtensionFilename()
        );
        $consoleExtension->unserialize($extension->serialize());

        return $consoleExtension;
    }

    /**
     * @param string   $testType
     * @param $fullPath
     * @return string
     */
    public function getTestPath($testType, $fullPath = false)
    {
        return $this->getPath($fullPath) . '/Tests/' . $testType;
    }

    public function validateModuleFunctionExist($moduleName, $function, $moduleFile = null)
    {
        //Load module file to prevent issue of missing functions used in update
        $module = $this->getModule($moduleName);
        $modulePath = $module->getPath();
        if ($moduleFile) {
            $this->site->loadLegacyFile($modulePath . '/'. $moduleFile);
        } else {
            $this->site->loadLegacyFile($modulePath . '/' . $module->getName() . '.module');
        }

        if (function_exists($function)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $moduleName
     * @param string $pluginType
     * @return string
     */
    public function getPluginPath($moduleName, $pluginType)
    {
        $module = $this->getModule($moduleName);

        return $module->getPath() . '/src/Plugin/'.$pluginType;
    }

    public function getDrupalExtension($type, $name)
    {
        $extension = $this->getExtension($type, $name);
        return $this->createExtension($extension);
    }

    /**
     * @param array  $extensions
     * @param string $type
     * @return array
     */
    public function checkExtensions(array $extensions, $type = 'module')
    {
        $checkextensions = [
            'local_extensions' => [],
            'drupal_extensions' => [],
            'no_extensions' => [],
        ];

        $local_extensions = $this->discoverExtension($type)
            ->showInstalled()
            ->showUninstalled()
            ->showCore()
            ->showNoCore()
            ->getList(true);

        foreach ($extensions as $extension) {
            if (in_array($extension, $local_extensions)) {
                $checkextensions['local_extensions'][] = $extension;
            } else {
                try {
                    $response = $this->httpClient->head('https://www.drupal.org/project/' . $extension);
                    $header_link = explode(';', $response->getHeader('link'));
                    if (empty($header_link[0])) {
                        $checkextensions['no_extensions'][] = $extension;
                    } else {
                        $checkextensions['drupal_extensions'][] = $extension;
                    }
                } catch (ClientException $e) {
                    $checkextensions['no_extensions'][] = $extension;
                }
            }
        }

        return $checkextensions;
    }
}
