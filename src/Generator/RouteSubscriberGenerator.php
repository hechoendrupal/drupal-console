<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\RouteSubscriberGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

class RouteSubscriberGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * AuthenticationProviderGenerator constructor.
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
        $class = $parameters['class'];
        $moduleInstance = $this->extensionManager->getModule($module);
        $moduleServiceYaml = $moduleInstance->getPath() . '/' . $module . '.services.yml';
        $parameters['class_path'] = sprintf('Drupal\%s\Routing\%s', $module, $class);
        $parameters['tags'] = ['name' => 'event_subscriber'];
        $parameters['file_exists'] = file_exists($moduleServiceYaml);

        $this->renderFile(
            'module/src/Routing/route-subscriber.php.twig',
            $moduleInstance->getRoutingPath() . '/' . $class . '.php',
            $parameters
        );

        $this->renderFile(
            'module/services.yml.twig',
            $moduleServiceYaml,
            $parameters,
            FILE_APPEND
        );
    }
}
