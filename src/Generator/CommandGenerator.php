<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\CommandGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Extension\Manager;

/**
 * Class CommandGenerator
 *
 * @package Drupal\Console\Generator
 */
class CommandGenerator extends Generator implements GeneratorInterface
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
     * @param array   $parameters
     */
    public function generate($parameters = []) {

        $extension = $parameters['extension'];
        $extensionType = $parameters['extension_type'];
        $name = $parameters['name'];
        $initialize = $parameters['initialize'];
        $interact = $parameters['interact'];
        $class = $parameters['class'];
        $containerAware = $parameters['container_aware'];
        $services = $parameters['services'];

        $command_key = str_replace(':', '.', $name);

        $extensionObject = $this->extensionManager->getDrupalExtension($extensionType, $extension);

        $template_parameters = [
            'extension' => $extension,
            'extensionType' => $extensionType,
            'name' => $name,
            'interact' => $interact,
            'initialize' => $initialize,
            'class_name' => $class,
            'container_aware' => $containerAware,
            'command_key' => $command_key,
            'services' => $services,
            'tags' => ['name' => 'drupal.command'],
            'class_path' => sprintf('Drupal\%s\Command\%s', $extension, $class),
            'file_exists' => file_exists($extensionObject->getPath() . '/console.services.yml'),
        ];

        $this->renderFile(
            'module/src/Command/command.php.twig',
            $extensionObject->getCommandDirectory() . $class . '.php',
            $template_parameters
        );

        $template_parameters['name'] = $extension . '.' . str_replace(':', '_', $name);

        $this->renderFile(
            'module/services.yml.twig',
            $extensionObject->getPath() . '/console.services.yml',
            $template_parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Command/console/translations/en/command.yml.twig',
            $extensionObject->getPath() . '/console/translations/en/' . $command_key . '.yml'
        );
    }
}
