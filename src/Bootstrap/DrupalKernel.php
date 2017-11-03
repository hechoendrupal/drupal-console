<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DrupalKernel as DrupalKernelBase;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Console\Bootstrap\ConsoleSettings;

/**
 * Class DrupalKernel
 *
 * @package Drupal\Console\Utils
 */
class DrupalKernel extends DrupalKernelBase
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
        $kernel->consoleInitializeSettings($request);
        $kernel->handle($request);
        return $kernel;
    }

    /**
     * @inheritdoc
     */
    protected function consoleInitializeSettings(Request $request) {
      $site_path = static::findSitePath($request);
      $this->setSitePath($site_path);
      $class_loader_class = get_class($this->classLoader);
      ConsoleSettings::initialize($this->root, $site_path, $this->classLoader);

      // Initialize our list of trusted HTTP Host headers to protect against
      // header attacks.
      $host_patterns = ConsoleSettings::get('trusted_host_patterns', []);
      if (PHP_SAPI !== 'cli' && !empty($host_patterns)) {
        if (static::setupTrustedHosts($request, $host_patterns) === FALSE) {
          throw new BadRequestHttpException('The provided host name is not valid for this server.');
        }
      }

      // If the class loader is still the same, possibly
      // upgrade to an optimized class loader.
      if ($class_loader_class == get_class($this->classLoader)
          && ConsoleSettings::get('class_loader_auto_detect', TRUE)) {
        $prefix = ConsoleSettings::getApcuPrefix('class_loader', $this->root);
        $loader = NULL;

        // We autodetect one of the following three optimized classloaders, if
        // their underlying extension exists.
        if (function_exists('apcu_fetch')) {
          $loader = new ApcClassLoader($prefix, $this->classLoader);
        }
        elseif (extension_loaded('wincache')) {
          $loader = new WinCacheClassLoader($prefix, $this->classLoader);
        }
        elseif (extension_loaded('xcache')) {
          $loader = new XcacheClassLoader($prefix, $this->classLoader);
        }
        if (!empty($loader)) {
          $this->classLoader->unregister();
          // The optimized classloader might be persistent and store cache misses.
          // For example, once a cache miss is stored in APCu clearing it on a
          // specific web-head will not clear any other web-heads. Therefore
          // fallback to the composer class loader that only statically caches
          // misses.
          $old_loader = $this->classLoader;
          $this->classLoader = $loader;
          // Our class loaders are preprended to ensure they come first like the
          // class loader they are replacing.
          $old_loader->register(TRUE);
          $loader->register(TRUE);
        }
      }
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
}
