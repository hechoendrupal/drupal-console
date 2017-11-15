<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Site\Settings;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Console\Core\Utils\DrupalFinder;

/**
 * Trait DrupalKernelTrait
 *
 * @package Drupal\Console\Bootstrap
 */
trait DrupalKernelTrait
{
    /**
     * @var ServiceModifierInterface[]
     */
    protected $serviceModifiers = [];

    /**
     * @inheritdoc
     */
    public static function createFromRequest(Request $request, $class_loader, $environment, $allow_dumping = true, $app_root = null)
    {
        $kernel = new static($environment, $class_loader, $allow_dumping, $app_root);
        static::bootEnvironment($app_root);
        $kernel->initializeSettings($request);

        return $kernel;
    }

    /**
     * @param \Drupal\Core\DependencyInjection\ServiceModifierInterface $serviceModifier
     */
    public function addServiceModifier(ServiceModifierInterface $serviceModifier)
    {
        $this->serviceModifiers[] = $serviceModifier;
    }

    /**
     * @inheritdoc
     */
    protected function getContainerBuilder()
    {
        $container = parent::getContainerBuilder();
        foreach ($this->serviceModifiers as $serviceModifier) {
            $serviceModifier->alter($container);
        }

        return $container;
    }

    /**
     * {@inheritdoc}
     */
    public function discoverServiceProviders()
    {
        // Discover Drupal service providers
        parent::discoverServiceProviders();

        // Discover Drupal Console service providers
        $this->discoverDrupalConsoleServiceProviders();
    }

    public function discoverDrupalConsoleServiceProviders() {
        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());

        // Add DrupalConsole module(s) services
        $this->addDrupalConsoleModuleServices($drupalFinder->getDrupalRoot());

        // Add DrupalConsole theme(s) services
        $this->addDrupalConsoleThemeServices($drupalFinder->getDrupalRoot());
    }

    protected function addDrupalConsoleModuleServices($root)
    {
        $servicesFiles  = [];
        $moduleFileNames = $this->getModuleFileNames();
        foreach ($moduleFileNames as $module => $filename) {
            $servicesFile = $root . '/' .
                dirname($filename) .
                "/console.services.yml";
            if (file_exists($servicesFile)) {
                $servicesFiles[] = $servicesFile;
            }
        }

        $this->addDrupalServiceFiles($servicesFiles);
    }

    public function addDrupalServiceFiles($servicesFiles) {
        $this->serviceYamls['site'] = array_merge(
            $this->serviceYamls['site'],
            $servicesFiles
        );
    }

    protected function addDrupalConsoleThemeServices($root)
    {
        $themes = $this->getThemeFileNames();
    }

    private function getThemeFileNames()
    {
        $extensions = $this->getConfigStorage()->read('core.extension');

        return isset($extensions['theme']) ? $extensions['theme'] : [];
    }
}
