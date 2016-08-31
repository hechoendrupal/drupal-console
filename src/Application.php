<?php

namespace Drupal\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Console\Utils\AnnotationValidator;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class Application
 * @package Drupal\Console
 */
class Application extends ConsoleApplication
{
    /**
     * @var string
     */
    const NAME = 'Drupal Console';

    /**
     * @var string
     */
    const VERSION = '1.0.0-rc1';

    /**
     * @var string
     */
    protected $commandName;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, $this::NAME, $this::VERSION);
        $this->addOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($commandName = $this->getCommandName($input)) {
            $this->commandName = $commandName;
        }
        $this->registerGenerators();
        $this->registerCommands();
        parent::doRun($input, $output);
        if ($this->getCommandName($input) == 'list' && $this->container->hasParameter('console.warning')) {
            $io = new DrupalStyle($input, $output);
            $io->warning(
                $this->trans($this->container->getParameter('console.warning'))
            );
        }
    }

    private function addOptions()
    {
        $this->getDefinition()->addOption(
            new InputOption(
                '--env',
                '-e',
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.env'), 'prod'
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--root',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.root')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--no-debug',
                null,
                InputOption::VALUE_NONE,
                $this->trans('application.options.no-debug')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--learning',
                null,
                InputOption::VALUE_NONE,
                $this->trans('application.options.learning')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--generate-chain',
                '-c',
                InputOption::VALUE_NONE,
                $this->trans('application.options.generate-chain')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--generate-inline',
                '-i',
                InputOption::VALUE_NONE,
                $this->trans('application.options.generate-inline')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--generate-doc',
                '-d',
                InputOption::VALUE_NONE,
                $this->trans('application.options.generate-doc')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--target',
                '-t',
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.target')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--uri',
                '-l',
                InputOption::VALUE_REQUIRED,
                $this->trans('application.options.uri')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--yes',
                '-y',
                InputOption::VALUE_NONE,
                $this->trans('application.options.yes')
            )
        );
    }

    private function registerGenerators()
    {
        if ($this->container->hasParameter('console.generators')) {
            $consoleGenerators = $this->container->getParameter(
                'console.generators'
            );
        } else {
            $consoleGenerators = array_keys(
                $this->container->findTaggedServiceIds('console.generator')
            );
        }

        foreach ($consoleGenerators as $name) {
            if (!$this->container->has($name)) {
                continue;
            }

            $generator = $this->container->get($name);

            if (!$generator) {
                continue;
            }

            if (method_exists($generator, 'setRenderer')) {
                $generator->setRenderer(
                    $this->container->get('console.renderer')
                );
            }

            if (method_exists($generator, 'setFileQueue')) {
                $generator->setFileQueue(
                    $this->container->get('console.file_queue')
                );
            }
        }
    }

    private function registerCommands()
    {
        $this->registerCommandsAsServices();
        $this->registerCommandsFromAutoWireConfiguration();
    }

    private function registerCommandsAsServices()
    {
        if ($this->container->hasParameter('console.commands')) {
            $consoleCommands = $this->container->getParameter(
                'console.commands'
            );
        } else {
            $consoleCommands = array_keys(
                $this->container->findTaggedServiceIds('console.command')
            );
            $this->container->setParameter(
                'console.warning',
                'application.site.errors.settings'
            );
        }

        $serviceDefinitions = [];
        $annotationValidator = null;
        if ($this->container->hasParameter('console.service_definitions')) {
            $serviceDefinitions = $this->container
                ->getParameter('console.service_definitions');

            /**
             * @var AnnotationValidator $annotationValidator
             */
            $annotationValidator = $this->container
                ->get('console.annotation_validator');
        }

        foreach ($consoleCommands as $name) {
            if (!$this->container->has($name)) {
                continue;
            }

            if ($annotationValidator) {
                if (!$serviceDefinition = $serviceDefinitions[$name]) {
                    continue;
                }

                if (!$annotationValidator->isValidCommand($serviceDefinition->getClass())) {
                    continue;
                }
            }

            try {
                $command = $this->container->get($name);
            } catch (\Exception $e) {
                continue;
            }

            if (!$command) {
                continue;
            }

            if (method_exists($command, 'setTranslator')) {
                $command->setTranslator(
                    $this->container->get('console.translator_manager')
                );
            }

            if (method_exists($command, 'setContainer')) {
                $command->setContainer(
                    $this->container->get('service_container')
                );
            }

            $this->add($command);
        }
    }

    private function registerCommandsFromAutoWireConfiguration()
    {
        $configuration = $this->container->get('console.configuration_manager')
            ->getConfiguration();

        $autoWireForcedCommands = $configuration->get(
            sprintf(
                'application.autowire.commands.forced'
            )
        );

        foreach ($autoWireForcedCommands as $autoWireForcedCommand) {
            try {
                $reflectionClass = new \ReflectionClass(
                    $autoWireForcedCommand['class']
                );

                $command = $reflectionClass->newInstance();

                if (method_exists($command, 'setTranslator')) {
                    $command->setTranslator(
                        $this->container->get('console.translator_manager')
                    );
                }
                if (method_exists($command, 'setContainer')) {
                    $command->setContainer(
                        $this->container->get('service_container')
                    );
                }

                $this->add($command);
            } catch (\Exception $e) {
                continue;
            }
        }

        $autoWireNameCommand = $configuration->get(
            sprintf(
                'application.autowire.commands.name.%s',
                $this->commandName
            )
        );

        if ($autoWireNameCommand) {
            try {
                $arguments = [];
                if (array_key_exists('arguments', $autoWireNameCommand)) {
                    foreach ($autoWireNameCommand['arguments'] as $argument) {
                        $argument = substr($argument, 1);
                        $arguments[] = $this->container->get($argument);
                    }
                }

                $reflectionClass = new \ReflectionClass(
                    $autoWireNameCommand['class']
                );
                $command = $reflectionClass->newInstanceArgs($arguments);

                if (method_exists($command, 'setTranslator')) {
                    $command->setTranslator(
                        $this->container->get('console.translator_manager')
                    );
                }
                if (method_exists($command, 'setContainer')) {
                    $command->setContainer(
                        $this->container->get('service_container')
                    );
                }

                $this->add($command);
            } catch (\Exception $e) {
            }
        }
    }
}
