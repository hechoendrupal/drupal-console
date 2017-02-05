<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\CacheContextGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class CacheContextGenerator extends Generator
{
    /**
   * @var Manager
   */
    protected $extensionManager;

    /**
   * CacheContextGenerator constructor.
   *
   * @param Manager $extensionManager
   */
    public function __construct(
        Manager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
   * Generator Service.
   *
   * @param string $module        Module name
   * @param string $cache_context Cache context name
   * @param string $class         Class name
   * @param array  $services      List of services
   */
    public function generate($module, $cache_context, $class, $services)
    {
        $parameters = [
        'module' => $module,
        'name' => 'cache_context.' . $cache_context,
        'class' => $class,
        'services' => $services,
        'class_path' => sprintf('Drupal\%s\CacheContext\%s', $module, $class),
        'tags' => ['name' => 'cache_context'],
        'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/src/cache-context.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath().'/CacheContext/'.$class.'.php',
            $parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
