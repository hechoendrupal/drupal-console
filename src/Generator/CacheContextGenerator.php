<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\CacheContextGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;

class CacheContextGenerator extends Generator implements GeneratorInterface
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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $module = $parameters['module'];
        $cache_context = $parameters['ache_context'];
        $class = $parameters['class'];
        $services = $parameters['services'];

        $moduleInstance = $this->extensionManager->getModule($module);
        $modulePath = $moduleInstance->getPath() . '/' . $module;

        $parameters = array_merge($parameters, [
            'name' => 'cache_context.' . $cache_context,
            'class_path' => sprintf('Drupal\%s\CacheContext\%s', $module, $class),
            'tags' => ['name' => 'cache.context'],
            'file_exists' => file_exists($modulePath . '.services.yml'),
        ]);

        $this->renderFile(
            'module/src/cache-context.php.twig',
            $moduleInstance->getSourcePath() . '/CacheContext/' . $class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $modulePath . '.services.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
