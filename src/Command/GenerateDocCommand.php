<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GenerateDocCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class GenerateDocCommand extends ContainerAwareCommand
{
    private $single_commands = [
      'chain',
      'drush',
      'help',
      'init',
      'list',
      'self-update'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:doc')
            ->setDescription($this->trans('commands.generate.doc.description'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.options.path')
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getHelperSet()->get('message');
        $renderer = $this->getHelperSet()->get('renderer');

        $path = null;
        if ($input->hasOption('path')) {
            $path = $input->getOption('path');
        }

        if (!$path) {
            $message->addErrorMessage(
                $this->trans('commands.generate.doc.messages.missing_path')
            );

            return 1;
        }

        $application = $this->getApplication();
        $command_list = [];

        foreach ($this->single_commands as $single_command) {
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
                if ($command->getModule()=='AppConsole') {
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

        $parameters = [
            'options' => $options,
            'arguments' => $arguments,
            'command' => $command->getName(),
            'description' => $command->getDescription(),
            'aliases' => $command->getAliases()
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
