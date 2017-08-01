<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginSkeletonCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginSkeletonGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Validator;

/**
 * Class PluginSkeletonCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginSkeletonCommand extends ContainerAwareCommand
{
    use ModuleTrait;
    use ConfirmationTrait;
    use ServicesTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginSkeletonGenerator
*/
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
 * @var Validator
*/
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginSkeletonCommand constructor.
     *
     * @param Manager                 $extensionManager
     * @param PluginSkeletonGenerator $generator
     * @param StringConverter         $stringConverter
     * @param Validator               $validator
     * @param ChainQueue              $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginSkeletonGenerator $generator,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected $pluginGeneratorsImplemented = [
        'block' => 'generate:plugin:block',
        'ckeditor.plugin' => 'generate:plugin:ckeditorbutton',
        'condition' => 'generate:plugin:condition',
        'field.formatter' => 'generate:plugin:fieldformatter',
        'field.field_type' => 'generate:plugin:fieldtype',
        'field.widget' =>'generate:plugin:fieldwidget',
        'image.effect' => 'generate:plugin:imageeffect',
        'mail' => 'generate:plugin:mail'
    ];

    protected function configure()
    {
        $this
            ->setName('generate:plugin:skeleton')
            ->setDescription($this->trans('commands.generate.plugin.skeleton.description'))
            ->setHelp($this->trans('commands.generate.plugin.skeleton.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.skeleton.options.plugin')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.skeleton.options.class')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL| InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )->setAliases(['gps']);
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $plugins = $this->getPlugins();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $module = $input->getOption('module');

        $pluginId = $input->getOption('plugin-id');
        $plugin = ucfirst($this->stringConverter->underscoreToCamelCase($pluginId));

        // Confirm that plugin.manager is available
        if (!$this->validator->validatePluginManagerServiceExist($pluginId, $plugins)) {
            throw new \Exception(
                sprintf(
                    $this->trans('commands.generate.plugin.skeleton.messages.plugin-dont-exist'),
                    $pluginId
                )
            );
        }

        if (array_key_exists($pluginId, $this->pluginGeneratorsImplemented)) {
            $io->warning(
                sprintf(
                    $this->trans('commands.generate.plugin.skeleton.messages.plugin-generator-implemented'),
                    $pluginId,
                    $this->pluginGeneratorsImplemented[$pluginId]
                )
            );
        }

        $className = $input->getOption('class');
        $services = $input->getOption('services');

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $buildServices = $this->buildServices($services);
        $pluginMetaData = $this->getPluginMetadata($pluginId);

        $this->generator->generate($module, $pluginId, $plugin, $className, $pluginMetaData, $buildServices);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $plugins = $this->getPlugins();
            $pluginId = $io->choiceNoList(
                $this->trans('commands.generate.plugin.skeleton.questions.plugin'),
                $plugins
            );
            $input->setOption('plugin-id', $pluginId);
        }

        if (array_key_exists($pluginId, $this->pluginGeneratorsImplemented)) {
            $io->warning(
                sprintf(
                    $this->trans('commands.generate.plugin.skeleton.messages.plugin-dont-exist'),
                    $pluginId,
                    $this->pluginGeneratorsImplemented[$pluginId]
                )
            );
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.plugin.skeleton.options.class'),
                sprintf('%s%s', 'Default', ucfirst($this->stringConverter->underscoreToCamelCase($pluginId))),
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --services option
        // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
        $services = $input->getOption('services');
        if (!$services) {
            $services = $this->servicesQuestion($io);
            $input->setOption('services', $services);
        }
    }

    protected function getPluginMetadata($pluginId)
    {
        $pluginMetaData = [
            'serviceId' => 'plugin.manager.' . $pluginId,
        ];

        // Load service and create reflection
        $service = \Drupal::service($pluginMetaData['serviceId']);

        $reflectionClass = new \ReflectionClass($service);

        // Get list of properties with $reflectionClass->getProperties();
        $pluginManagerProperties = [
            'subdir' => 'subdir',
            'pluginInterface' => 'pluginInterface',
            'pluginDefinitionAnnotationName' => 'pluginAnnotation',
        ];

        foreach ($pluginManagerProperties as $propertyName => $key) {
            if (!$reflectionClass->hasProperty($propertyName)) {
                $pluginMetaData[$key] = '';
                continue;
            }

            $property = $reflectionClass->getProperty($propertyName);
            $property->setAccessible(true);
            $pluginMetaData[$key] = $property->getValue($service);
        }

        if (empty($pluginMetaData['pluginInterface'])) {
            $pluginMetaData['pluginInterfaceMethods'] = [];
        } else {
            $pluginMetaData['pluginInterfaceMethods'] = $this->getClassMethods($pluginMetaData['pluginInterface']);
        }

        if (isset($pluginMetaData['pluginAnnotation']) && class_exists($pluginMetaData['pluginAnnotation'])) {
            $pluginMetaData['pluginAnnotationProperties'] = $this->getPluginAnnotationProperties($pluginMetaData['pluginAnnotation']);
        } else {
            $pluginMetaData['pluginAnnotationProperties'] = [];
        }

        return $pluginMetaData;
    }

    /**
     * Get data for the methods of a class.
     *
     * @param $class
     *  The fully-qualified name of class.
     *
     * @return
     *  An array keyed by method name, where each value is an array containing:
     *  - 'name: The name of the method.
     *  - 'declaration': The function declaration line.
     *  - 'description': The description from the method's docblock first line.
     */
    protected function getClassMethods($class)
    {
        // Get a reflection class.
        $classReflection = new \ReflectionClass($class);
        $methods = $classReflection->getMethods();

        $metaData = [];
        $methodData = [];

        foreach ($methods as $method) {
            $methodData['name'] = $method->getName();

            $filename = $method->getFileName();
            $source = file($filename);
            $startLine = $method->getStartLine();

            $methodData['declaration'] = substr(trim($source[$startLine - 1]), 0, -1);

            $methodDocComment = explode("\n", $method->getDocComment());
            foreach ($methodDocComment as $line) {
                if (substr($line, 0, 5) == '   * ') {
                    $methodData['description'] = substr($line, 5);
                    break;
                }
            }

            $metaData[$method->getName()] = $methodData;
        }

        return $metaData;
    }

    /**
     * Get the list of properties from an annotation class.
     *
     * @param $pluginAnnotationClass
     *  The fully-qualified name of the plugin annotation class.
     *
     * @return
     *  An array keyed by property name, where each value is an array containing:
     *  - 'name: The name of the property.
     *  - 'description': The description from the property's docblock first line.
     */
    protected function getPluginAnnotationProperties($pluginAnnotationClass)
    {
        // Get a reflection class for the annotation class.
        // Each property of the annotation class describes a property for the
        // plugin annotation.
        $annotationReflection = new \ReflectionClass($pluginAnnotationClass);
        $propertiesReflection = $annotationReflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $pluginProperties = [];
        $annotationPropertyMetadata = [];

        foreach ($propertiesReflection as $propertyReflection) {
            $annotationPropertyMetadata['name'] = $propertyReflection->name;

            $propertyDocblock = $propertyReflection->getDocComment();
            $propertyDocblockLines = explode("\n", $propertyDocblock);
            foreach ($propertyDocblockLines as $line) {
                if (substr($line, 0, 3) == '/**') {
                    continue;
                }

                // Take the first actual docblock line to be the description.
                if (!isset($annotationPropertyMetadata['description']) && substr($line, 0, 5) == '   * ') {
                    $annotationPropertyMetadata['description'] = substr($line, 5);
                }

                // Look for a @var token, to tell us the type of the property.
                if (substr($line, 0, 10) == '   * @var ') {
                    $annotationPropertyMetadata['type'] = substr($line, 10);
                }
            }

            $pluginProperties[$propertyReflection->name] = $annotationPropertyMetadata;
        }

        return $pluginProperties;
    }

    protected function getPlugins()
    {
        $plugins = [];

        foreach ($this->container->getServiceIds() as $serviceId) {
            if (strpos($serviceId, 'plugin.manager.') === 0) {
                $plugins[] = substr($serviceId, 15);
            }
        }

        return $plugins;
    }
}
