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
     * @param string  $extension       Extension name
     * @param string  $extensionType   Extension type
     * @param string  $name            Command name
     * @param string  $initialize      Initialize method
     * @param string  $interact        Interact method
     * @param string  $class           Class name
     * @param boolean $containerAware  Container Aware command
     * @param array   $services        Services array
     * @param boolean $generator       Generate generator
     * @param string  $class_generator Generator Class name
     */
    public function generate(
        $extension,
        $extensionType,
        $name,
        $initialize,
        $interact,
        $class,
        $containerAware,
        $services,
        $generator,
        $class_generator
    ) {
        $command_key = str_replace(':', '.', $name);

        $extensionObject = $this->extensionManager
            ->getDrupalExtension($extensionType, $extension);

        $parameters = [
            'extension' => $extension,
            'extensionType' => $extensionType,
            'name' => $name,
            'interact' => $interact,
            'initialize' => $initialize,
            'class_name' => $class,
            'container_aware' => $containerAware,
            'command_key' => $command_key,
            'services' => $services,
            'tags' => [ 'name' => 'drupal.command' ],
            'class_path' => sprintf('Drupal\%s\Command\%s', $extension, $class),
            'file_exists' => file_exists($extensionObject->getPath().'/console.services.yml'),
            'class_generator' => $class_generator,
            'class_generator_path' => sprintf('Drupal\%s\Command\%s', $extension, $class_generator),
        ];

        $commandServiceName = $extension.'.'.str_replace(':', '_', $name);
        $generatorServiceName = $commandServiceName.'_generator';

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
            $extensionObject->getCommandDirectory().$class.'.php',
            $parameters
        );

        $parameters['name'] = $commandServiceName;

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
