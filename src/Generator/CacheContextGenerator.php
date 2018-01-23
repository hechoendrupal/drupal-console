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

        $template_parameters = [
            'module' => $module,
            'name' => 'cache_context.' . $cache_context,
            'class' => $class,
            'services' => $services,
            'class_path' => sprintf('Drupal\%s\CacheContext\%s', $module, $class),
            'tags' => ['name' => 'cache.context'],
            'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() . '/' . $module . '.services.yml'),
        ];

        $this->renderFile(
            'module/src/cache-context.php.twig',
            $this->extensionManager->getModule($module)->getSourcePath() . '/CacheContext/' . $class . '.php',
             $template_parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() . '/' . $module . '.services.yml',
             $template_parameters,
            FILE_APPEND
        );
    }
}
