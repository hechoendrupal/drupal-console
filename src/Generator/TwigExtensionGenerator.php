<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\TwigExtensionGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;

/**
 * Class TwigExtensionGenerator
 *
 * @package Drupal\Console\Generator
 */
class TwigExtensionGenerator extends Generator
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
        $modulePath = $this->extensionManager->getModule($module)->getPath();
        $moduleServiceYaml = $modulePath . '/' . $module . '.services.yml';
        $parameters['class_path'] = sprintf('Drupal\%s\TwigExtension\%s', $module, $class);
        $parameters['tags'] = ['name' => 'twig.extension'];
        $parameters['file_exists'] = file_exists($moduleServiceYaml);

        $this->renderFile(
            'module/services.yml.twig',
            $moduleServiceYaml,
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/TwigExtension/twig-extension.php.twig',
            $modulePath . '/src/TwigExtension/' . $class . '.php',
            $parameters
        );
    }
}
