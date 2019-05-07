<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\CommandGenerator.
 */

namespace Drupal\Console\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Extension\Manager;

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
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $extension = $parameters['extension'];
        $extensionType = $parameters['extension_type'];
        $name = $parameters['name'];
        $class = $parameters['class_name'];
        $class_generator = $parameters['class_generator'];
        $generator = $parameters['generator'];

        $command_key = str_replace(':', '.', $name);

        $extensionInstance = $this->extensionManager
            ->getDrupalExtension($extensionType, $extension);

        $extensionObjectPath = $extensionInstance->getPath();

        $parameters = array_merge(
            $parameters, [
                'command_key' => $command_key,
                'tags' => [ 'name' => 'drupal.command' ],
                'class_path' => sprintf('Drupal\%s\Command\%s', $extension, $class),
                'file_exists' => file_exists($extensionObjectPath . '/console.services.yml'),
            ]
        );

        $commandServiceName = $extension . '.' . str_replace(':', '_', $name);
        $generatorServiceName = $commandServiceName . '_generator';

        if ($generator) {
            $machineName = str_replace('.', '_', $generatorServiceName);
            $parameters['services'][$generatorServiceName] = [
                'name' => $generatorServiceName,
                'machine_name' => $machineName,
                'camel_case_name' => 'generator',
                'class' => 'Drupal\Console\Core\Generator\GeneratorInterface',
                'short' => 'GeneratorInterface',
            ];
        }

        $this->renderFile(
            'module/src/Command/command.php.twig',
            $extensionInstance->getCommandDirectory() . $class . '.php',
            $parameters
        );

        $parameters['name'] = $commandServiceName;

        $this->renderFile(
            'module/services.yml.twig',
            $extensionObjectPath . '/console.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Command/console/translations/en/command.yml.twig',
            $extensionObjectPath . '/console/translations/en/' . $command_key . '.yml'
        );

        if ($generator) {
            $parameters = array_merge(
                $parameters,
                [
                    'name' => $generatorServiceName,
                    'class_name' => $class_generator,
                    'services' => [],
                    'tags' => [ 'name' => 'drupal.generator' ],
                    'class_path' => sprintf('Drupal\%s\Generator\%s', $extension, $class_generator),
                    'file_exists' => file_exists($extensionObjectPath . '/console.services.yml'),
                ]
            );

            $this->renderFile(
                'module/src/Generator/generator.php.twig',
                $extensionInstance->getGeneratorDirectory() . $class_generator . '.php',
                $parameters
            );

            $this->renderFile(
                'module/services.yml.twig',
                $extensionObjectPath .'/console.services.yml',
                $parameters,
                FILE_APPEND
            );
        }
    }
}
