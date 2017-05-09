<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\CommandGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Generator\Generator;

/**
 * Class CommandGenerator
 *
 * @package Drupal\Console\Generator
 */
class CommandGenerator extends Generator
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var TranslatorManagerInterface
     */
    protected $translatorManager;

    /**
     * CommandGenerator constructor.
     *
     * @param Manager                    $extensionManager
     * @param TranslatorManagerInterface $translatorManager
     */
    public function __construct(
        Manager $extensionManager,
        TranslatorManagerInterface $translatorManager
    ) {
        $this->extensionManager = $extensionManager;
        $this->translatorManager = $translatorManager;
    }

    /**
     * Generate.
     *
     * @param string  $module         Module name
     * @param string  $name           Command name
     * @param string  $class          Class name
     * @param boolean $containerAware Container Aware command
     * @param array   $services       Services array
     */
    public function generate($module, $name, $class, $containerAware, $services)
    {
        $command_key = str_replace(':', '.', $name);

        $parameters = [
            'module' => $module,
            'name' => $name,
            'class_name' => $class,
            'container_aware' => $containerAware,
            'command_key' => $command_key,
            'services' => $services,
            'tags' => ['name' => 'drupal.command'],
            'class_path' => sprintf('Drupal\%s\Command\%s', $module, $class),
            'file_exists' => file_exists($this->extensionManager->getModule($module)->getPath() .'/'.$module.'.services.yml'),
        ];

        $this->renderFile(
            'module/src/Command/command.php.twig',
            $this->extensionManager->getModule($module)->getCommandDirectory().'/'.$class.'.php',
            $parameters
        );

        $parameters['name'] = $module.'.'.str_replace(':', '_', $name);

        $this->renderFile(
            'module/services.yml.twig',
            $this->extensionManager->getModule($module)->getPath() .'/console.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Command/console/translations/en/command.yml.twig',
            $this->extensionManager->getModule($module)->getPath().'/console/translations/en/'.$command_key.'.yml'
        );
    }
}
