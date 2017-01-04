<?php

namespace Drupal\Console;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Console\Annotations\DrupalCommandAnnotationReader;
use Drupal\Console\Utils\AnnotationValidator;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Application as BaseApplication;

/**
 * Class Application
 *
 * @package Drupal\Console
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    const NAME = 'Drupal Console';

    /**
     * @var string
     */
    const VERSION = '1.0.0-rc14';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, $this::NAME, $this::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->registerGenerators();
        $this->registerCommands();
//        for ($lines = 0; $lines < 2; $lines++) {
//            $io->write("\r\033[K\033[1A\r");
//        }
//        $io->clearCurrentLine();

        $clear = $this->container->get('console.configuration_manager')
            ->getConfiguration()
            ->get('application.clear')?:false;
        if ($clear === true || $clear === 'true') {
            $output->write(sprintf("\033\143"));
        }
        parent::doRun($input, $output);
        if ($this->getCommandName($input) == 'list' && $this->container->hasParameter('console.warning')) {
            $io->warning(
                $this->trans($this->container->getParameter('console.warning'))
            );
        }
    }

    private function registerGenerators()
    {
        if ($this->container->hasParameter('drupal.generators')) {
            $consoleGenerators = $this->container->getParameter(
                'drupal.generators'
            );
        } else {
            $consoleGenerators = array_keys(
                $this->container->findTaggedServiceIds('drupal.generator')
            );
        }

        foreach ($consoleGenerators as $name) {
            if (!$this->container->has($name)) {
                continue;
            }

            try {
                $generator = $this->container->get($name);
            } catch (\Exception $e) {
                echo $name . ' - ' . $e->getMessage() . PHP_EOL;

                continue;
            }

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
        if ($this->container->hasParameter('drupal.commands')) {
            $consoleCommands = $this->container->getParameter(
                'drupal.commands'
            );
        } else {
            $consoleCommands = array_keys(
                $this->container->findTaggedServiceIds('drupal.command')
            );
            $this->container->setParameter(
                'console.warning',
                'application.site.errors.settings'
            );
        }

        $serviceDefinitions = [];
        $annotationValidator = null;
        $annotationCommandReader = null;
        if ($this->container->hasParameter('console.service_definitions')) {
            $serviceDefinitions = $this->container
                ->getParameter('console.service_definitions');

            /**
             * @var DrupalCommandAnnotationReader $annotationCommandReader
             */
            $annotationCommandReader = $this->container
                ->get('console.annotation_command_reader');

            /**
             * @var AnnotationValidator $annotationValidator
             */
            $annotationValidator = $this->container
                ->get('console.annotation_validator');
        }

        $aliases = $this->container->get('console.configuration_manager')
            ->getConfiguration()
            ->get('application.commands.aliases')?:[];

        foreach ($consoleCommands as $name) {
            AnnotationRegistry::reset();
            AnnotationRegistry::registerLoader(
                [
                    $this->container->get('class_loader'),
                    "loadClass"
                ]
            );

            if (!$this->container->has($name)) {
                continue;
            }

            if ($annotationValidator && $annotationCommandReader) {
                if (!$serviceDefinition = $serviceDefinitions[$name]) {
                    continue;
                }

                if ($annotation = $annotationCommandReader->readAnnotation($serviceDefinition->getClass())) {
                    $this->container->get('console.translator_manager')
                        ->addResourceTranslationsByExtension(
                            $annotation['extension'],
                            $annotation['extensionType']
                        );
                }

                if (!$annotationValidator->isValidCommand($serviceDefinition->getClass())) {
                    continue;
                }
            }

            try {
                $command = $this->container->get($name);
            } catch (\Exception $e) {
                echo $name . ' - ' . $e->getMessage() . PHP_EOL;

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

            if (array_key_exists($command->getName(), $aliases)) {
                $commandAliases = $aliases[$command->getName()];
                if (!is_array($commandAliases)) {
                    $commandAliases = [$commandAliases];
                }
                $command->setAliases($commandAliases);
            }

            $this->add($command);
        }
    }

    public function getData()
    {
        $singleCommands = [
            'about',
            'chain',
            'check',
            'exec',
            'help',
            'init',
            'list',
            'server'
        ];
        $languages = $this->container->get('console.configuration_manager')
            ->getConfiguration()
            ->get('application.languages');

        $data = [];
        foreach ($singleCommands as $singleCommand) {
            $data['commands']['misc'][] = $this->commandData($singleCommand);
        }

        $namespaces = array_filter(
            $this->getNamespaces(), function ($item) {
            return (strpos($item, ':')<=0);
        }
        );
        sort($namespaces);
        array_unshift($namespaces, 'misc');

        foreach ($namespaces as $namespace) {
            $commands = $this->all($namespace);
            usort(
                $commands, function ($cmd1, $cmd2) {
                return strcmp($cmd1->getName(), $cmd2->getName());
            }
            );

            foreach ($commands as $command) {
                if (method_exists($command, 'getModule')) {
                    if ($command->getModule() == 'Console') {
                        $data['commands'][$namespace][] = $this->commandData(
                            $command->getName()
                        );
                    }
                } else {
                    $data['commands'][$namespace][] = $this->commandData(
                        $command->getName()
                    );
                }
            }
        }

        $input = $this->getDefinition();
        $options = [];
        foreach ($input->getOptions() as $option) {
            $options[] = [
                'name' => $option->getName(),
                'description' => $this->trans('application.options.'.$option->getName())
            ];
        }
        $arguments = [];
        foreach ($input->getArguments() as $argument) {
            $arguments[] = [
                'name' => $argument->getName(),
                'description' => $this->trans('application.arguments.'.$argument->getName())
            ];
        }

        $data['application'] = [
            'namespaces' => $namespaces,
            'options' => $options,
            'arguments' => $arguments,
            'languages' => $languages,
            'messages' => [
                'title' =>  $this->trans('commands.generate.doc.gitbook.messages.title'),
                'note' =>  $this->trans('commands.generate.doc.gitbook.messages.note'),
                'note_description' =>  $this->trans('commands.generate.doc.gitbook.messages.note-description'),
                'command' =>  $this->trans('commands.generate.doc.gitbook.messages.command'),
                'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
            ],
            'examples' => []
        ];

        return $data;
    }

    private function commandData($commandName)
    {
        if (!$this->has($commandName)) {
            return [];
        }

        $command = $this->find($commandName);

        $input = $command->getDefinition();
        $options = [];
        foreach ($input->getOptions() as $option) {
            $options[$option->getName()] = [
                'name' => $option->getName(),
                'description' => $this->trans($option->getDescription()),
            ];
        }

        $arguments = [];
        foreach ($input->getArguments() as $argument) {
            $arguments[$argument->getName()] = [
                'name' => $argument->getName(),
                'description' => $this->trans($argument->getDescription()),
            ];
        }

        $commandKey = str_replace(':', '.', $command->getName());

        $examples = [];
        for ($i = 0; $i < 5; $i++) {
            $description = sprintf(
                'commands.%s.examples.%s.description',
                $commandKey,
                $i
            );
            $execution = sprintf(
                'commands.%s.examples.%s.execution',
                $commandKey,
                $i
            );

            if ($description != $this->trans($description)) {
                $examples[] = [
                    'description' => $this->trans($description),
                    'execution' => $this->trans($execution)
                ];
            } else {
                break;
            }
        }

        $data = [
            'name' => $command->getName(),
            'description' => $command->getDescription(),
            'options' => $options,
            'arguments' => $arguments,
            'examples' => $examples,
            'aliases' => $command->getAliases(),
            'key' => $commandKey,
            'dashed' => str_replace(':', '-', $command->getName()),
            'messages' => [
                'usage' =>  $this->trans('commands.generate.doc.gitbook.messages.usage'),
                'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
            ],
        ];

        return $data;
    }

    public function setContainer($container)
    {
        $this->container = $container;
        $this->registerGenerators();
        $this->registerCommands();
    }
}
