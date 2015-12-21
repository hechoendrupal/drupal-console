<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\GenerateDocGitbookCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Command\ContainerAwareCommand;

class GenerateDocGitbookCommand extends ContainerAwareCommand
{
    private $singleCommands = [
      'about',
      'chain',
      'help',
      'init',
      'list',
      'server'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:doc:gitbook')
            ->setDescription($this->trans('commands.generate.doc.gitbook.description'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.gitbook.options.path')
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getMessageHelper();
        $renderer = $this->getRenderHelper();

        $path = null;
        if ($input->hasOption('path')) {
            $path = $input->getOption('path');
        }

        if (!$path) {
            $message->addErrorMessage(
                $this->trans('commands.generate.doc.gitbook.messages.missing_path')
            );

            return 1;
        }

        $application = $this->getApplication();
        $command_list = [];

        foreach ($this->singleCommands as $single_command) {
            $command = $application->find($single_command);
            $command_list['none'][] = [
                'name' => $command->getName(),
                'description' => $command->getDescription(),
            ];
            $this->renderCommand($command, $path, $renderer);
        }

        $namespaces = $application->getNamespaces();
        sort($namespaces);

        $namespaces = array_filter(
            $namespaces, function ($item) {
                return (strpos($item, ':')<=0);
            }
        );

        foreach ($namespaces as $namespace) {
            $commands = $application->all($namespace);

            usort(
                $commands, function ($cmd1, $cmd2) {
                    return strcmp($cmd1->getName(), $cmd2->getName());
                }
            );

            foreach ($commands as $command) {
                if ($command->getModule()=='Console') {
                    $command_list[$namespace][] = [
                        'name' => $command->getName(),
                        'description' => $command->getDescription(),
                    ];
                    $this->renderCommand($command, $path, $renderer);
                }
            }
        }

        $input = $application->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();
        $parameters = [
            'command_list' => $command_list,
            'options' => $options,
            'arguments' => $arguments,
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

        $this->renderFile(
            'gitbook/available-commands.md.twig',
            $path . '/commands/available-commands.md',
            $parameters,
            null,
            $renderer
        );

        $this->renderFile(
            'gitbook/available-commands-list.md.twig',
            $path . '/commands/available-commands-list.md',
            $parameters,
            null,
            $renderer
        );
    }

    private function renderCommand($command, $path, $renderer)
    {
        $input = $command->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();

        $commandKey = str_replace(':', '.', $command->getName());

        $examples = [];
        $index = 0;
        while (true) {
            $description = sprintf(
                'commands.%s.examples.%s.description',
                $commandKey,
                $index
            );
            $execution = sprintf(
                'commands.%s.examples.%s.execution',
                $commandKey,
                $index
            );

            if ($description != $this->trans($description)) {
                $examples[] = [
                    'description' => $this->trans($description),
                    'execution' => $this->trans($execution)
                ];
            } else {
                break;
            }
            $index++;
        }

        $parameters = [
            'options' => $options,
            'arguments' => $arguments,
            'command' => $command->getName(),
            'description' => $command->getDescription(),
            'aliases' => $command->getAliases(),
            'messages' => [
                'command_description' => sprintf($this->trans('commands.generate.doc.gitbook.messages.command_description'), $command->getName(), $command->getDescription()),
                'usage' =>  $this->trans('commands.generate.doc.gitbook.messages.usage'),
                'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
            ],
            'examples' => $examples
        ];

        $this->renderFile(
            'gitbook/generate-doc.md.twig',
            $path . '/commands/' . str_replace(':', '-', $command->getName()) . '.md',
            $parameters,
            null,
            $renderer
        );
    }

    private function renderFile($template, $target, $parameters, $flag = null, $renderer)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $renderer->render($template, $parameters), $flag);
    }
}
