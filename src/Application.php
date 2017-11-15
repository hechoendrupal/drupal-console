<?php

namespace Drupal\Console;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Console\Annotations\DrupalCommandAnnotationReader;
use Drupal\Console\Utils\AnnotationValidator;
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
    const VERSION = '1.2.0';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, $this::NAME, $this::VERSION);
    }

    /**
     * Returns the long version of the application.
     *
     * @return string The long application version
     */
    public function getLongVersion()
    {
        $output = '';

        if ('UNKNOWN' !== $this->getName()) {
            if ('UNKNOWN' !== $this->getVersion()) {
                $output .= sprintf('<info>%s</info> version <comment>%s</comment>', $this->getName(), $this->getVersion());
            } else {
                $output .= sprintf('<info>%s</info>', $this->getName());
            }
        } else {
            $output .= '<info>Drupal Console</info>';
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->validateCommands();

        return parent::doRun($input, $output);
    }

    public function validateCommands()
    {
        $consoleCommands = $this->container
            ->findTaggedServiceIds('drupal.command');

        if (!$consoleCommands) {
            return;
        }

        if (!$this->container->hasParameter('console.service_definitions')) {
            return;
        }

        $serviceDefinitions = $this->container
            ->getParameter('console.service_definitions');

        if (!$serviceDefinitions) {
            return;
        }

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

        $invalidCommands = [];

        foreach ($consoleCommands as $name => $tags) {
            AnnotationRegistry::reset();
            AnnotationRegistry::registerLoader(
                [
                    $this->container->get('class_loader'),
                    "loadClass"
                ]
            );

            if (!$this->container->has($name)) {
                $invalidCommands[] = $name;
                continue;
            }

            if (!$serviceDefinition = $serviceDefinitions[$name]) {
                $invalidCommands[] = $name;
                continue;
            }

            if (!$annotationValidator->isValidCommand(
                $serviceDefinition->getClass()
            )
            ) {
                $invalidCommands[] = $name;
                continue;
            }

            $annotation = $annotationCommandReader
                ->readAnnotation($serviceDefinition->getClass());
            if ($annotation) {
                $this->container->get('console.translator_manager')
                    ->addResourceTranslationsByExtension(
                        $annotation['extension'],
                        $annotation['extensionType']
                    );
            }
        }

        $this->container->set(
            'console.invalid_commands',
            $invalidCommands
        );

        return;
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
            'shell',
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
                'title' => $this->trans('application.gitbook.messages.title'),
                'note' =>  $this->trans('application.gitbook.messages.note'),
                'note_description' =>  $this->trans('application.gitbook.messages.note-description'),
                'command' =>  $this->trans('application.gitbook.messages.command'),
                'options' => $this->trans('application.gitbook.messages.options'),
                'option' => $this->trans('application.gitbook.messages.option'),
                'details' => $this->trans('application.gitbook.messages.details'),
                'arguments' => $this->trans('application.gitbook.messages.arguments'),
                'argument' => $this->trans('application.gitbook.messages.argument'),
                'examples' => $this->trans('application.gitbook.messages.examples')
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
                'usage' =>  $this->trans('application.gitbook.messages.usage'),
                'options' => $this->trans('application.gitbook.messages.options'),
                'option' => $this->trans('application.gitbook.messages.option'),
                'details' => $this->trans('application.gitbook.messages.details'),
                'arguments' => $this->trans('application.gitbook.messages.arguments'),
                'argument' => $this->trans('application.gitbook.messages.argument'),
                'examples' => $this->trans('application.gitbook.messages.examples')
            ],
        ];

        return $data;
    }
}
