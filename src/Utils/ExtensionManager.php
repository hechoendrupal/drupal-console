<?php

namespace Drupal\Console\Utils;

/**
 * Class ExtensionManager
 * @package Drupal\Console\Utils
 */
class ExtensionManager {

    protected $drupalApi;
    protected $appRoot;

    /**
     * @var array
     */
    private $extensions = [
        'module' =>[],
        'theme' =>[],
        'profile' =>[],
    ];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * ExtensionManager constructor.
     * @param $drupalApi
     * @param $appRoot
     */
    public function __construct($drupalApi, $appRoot) {
        $this->drupalApi = $drupalApi;
        $this->appRoot = $appRoot;
        $this->initializeFilters();
    }

    /**
     * @return $this
     */
    public function showInstalled() {
        $this->filters['showInstalled'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showUninstalled() {
        $this->filters['showUninstalled'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showCore() {
        $this->filters['showCore'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showNoCore() {
        $this->filters['showNoCore'] = true;
        return $this;
    }

    /**
     * @param string    $nameOnly
     * @return array
     */
    public function getModules($nameOnly) {
        return $this->getExtensions('module', $nameOnly);
    }

    /**
     * @return $this
     */
    public function discoverModules()
    {
        $this->initializeFilters();
        $this->extensions['module'] = $this->discoverExtensions('module');

        return $this;
    }

    /**
     * @return $this
     */
    public function discoverThemes()
    {
        $this->initializeFilters();
        $this->extensions['theme'] = $this->discoverExtensions('theme');

        return $this;
    }

    /**
     * @return $this
     */
    public function discoverProfiles()
    {
        $this->initializeFilters();
        $this->extensions['profile'] = $this->discoverExtensions('profile');

        return $this;
    }

    /**
     * initializeFilters
     */
    private function initializeFilters() {
        $this->filters = [
            'showInstalled' => false,
            'showUninstalled' => false,
            'showCore' => false,
            'showNoCore' => false
        ];
    }

    /**
     * @param string        $type
     * @param bool|false    $nameOnly
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
        if (!$this->extensions[$type]) {
            return [];
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
     * @param string $type
     * @return \Drupal\Core\Extension\Extension[]
     */
    private function discoverExtensions($type)
    {
        $this->drupalApi->loadLegacyFile('/core/modules/system/system.module');
        system_rebuild_module_data();

        /*
         * @see Remove DrupalExtensionDiscovery subclass once
         * https://www.drupal.org/node/2503927 is fixed.
         */
        $discovery = new DrupalExtensionDiscovery($this->appRoot);
        $discovery->reset();

        return $discovery->scan($type);
    }
}
