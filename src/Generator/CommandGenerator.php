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
     * @param string  $extension      Extension name
     * @param string  $extensionType  Extension type
     * @param string  $name           Command name
     * @param string  $interact       Interact
     * @param string  $class          Class name
     * @param boolean $containerAware Container Aware command
     * @param array   $services       Services array
     */
    public function generate($extension, $extensionType, $name,$interact, $class, $containerAware, $services)
    {
        $command_key = str_replace(':', '.', $name);

        $extensionObject = $this->extensionManager->getDrupalExtension($extensionType, $extension);

        $parameters = [
            'extension' => $extension,
            'extensionType' => $extensionType,
            'name' => $name,
            'interact' => $interact,
            'class_name' => $class,
            'container_aware' => $containerAware,
            'command_key' => $command_key,
            'services' => $services,
            'tags' => ['name' => 'drupal.command'],
            'class_path' => sprintf('Drupal\%s\Command\%s', $extension, $class),
            'file_exists' => file_exists($extensionObject->getPath().'/console.services.yml'),
        ];

        $this->renderFile(
            'module/src/Command/command.php.twig',
            $extensionObject->getCommandDirectory().$class.'.php',
            $parameters
        );

        $parameters['name'] = $extension.'.'.str_replace(':', '_', $name);

        $this->renderFile(
            'module/services.yml.twig',
            $extensionObject->getPath() .'/console.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Command/console/translations/en/command.yml.twig',
            $extensionObject->getPath().'/console/translations/en/'.$command_key.'.yml'
        );
    }
}
