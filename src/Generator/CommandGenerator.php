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
     * {@inheritdoc}
     */
    public function generate(array $parameters) {

        $extension = $parameters['extension'];
        $extensionType = $parameters['extension_type'];
        $name = $parameters['name'];
        $class = $parameters['class_name'];
        $class_generator = $parameters['class_generator'];
        $generator = $parameters['generator'];

        $command_key = str_replace(':', '.', $name);

        $extensionObject = $this->extensionManager
            ->getDrupalExtension($extensionType, $extension);

        $parameters = array_merge(
            $parameters,[
            'command_key' => $command_key,
            'tags' => [ 'name' => 'drupal.command' ],
            'class_path' => sprintf('Drupal\%s\Command\%s', $extension, $class),
            'file_exists' => file_exists($extensionObject->getPath() . '/console.services.yml'),
            'class_generator_path' => sprintf('Drupal\%s\Command\%s', $extension, $class_generator),
        ]);

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
            $extensionObject->getCommandDirectory() . $class . '.php',
            $parameters
        );

        $parameters['name'] = $commandServiceName;

        $this->renderFile(
            'module/services.yml.twig',
            $extensionObject->getPath() . '/console.services.yml',
            $parameters,
            FILE_APPEND
        );

        $this->renderFile(
            'module/src/Command/console/translations/en/command.yml.twig',
            $extensionObject->getPath() . '/console/translations/en/' . $command_key . '.yml'
        );

        if ($generator) {
            $this->renderFile(
                'module/src/Generator/generator.php.twig',
                $extensionObject->getGeneratorDirectory() . $class_generator . '.php',
                $parameters
            );

            $parameters = array_merge(
                $parameters,
                [
                    'name' => $generatorServiceName,
                    'class_name' => $class_generator,
                    'services' => [],
                    'tags' => [ 'name' => 'drupal.generator' ],
                    'class_path' => sprintf('Drupal\%s\Generator\%s', $extension, $class_generator),
                    'file_exists' => file_exists($extensionObject->getPath().'/console.services.yml'),
                    'class_generator' => $class_generator,
                    'class_generator_path' => sprintf('Drupal\%s\Generator\%s', $extension, $class_generator),
                ]
            );

            $this->renderFile(
                'module/services.yml.twig',
                $extensionObject->getPath() .'/console.services.yml',
                $parameters,
                FILE_APPEND
            );
        }
    }
}
