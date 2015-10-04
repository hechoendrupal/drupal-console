<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GenerateCommandTestCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class GenerateCommandTestCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:command:test')
            ->setDescription('generate:command:test')
            ->addOption(
                'command-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.chain.options.file')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandName = $input->getOption('command-name');
        $renderer = $this->getRenderHelper();
        $application = $this->getApplication();
        $command = $application->find($commandName);
        $this->renderCommand($command, '', $renderer);
    }

    private function renderCommand($command, $path, $renderer)
    {
        $input = $command->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();
        $command_class_name = str_replace('Drupal\\Console\\Command\\', '', get_class($command));
        $command_name =  str_replace('Command', '', $command_class_name);

        $parameters = [
            'options' => $options,
            'arguments' => $arguments,
            'class' => $command_class_name,
            'command_name' => $command_name
        ];

        $this->renderFile(
            'core/test/command.php.twig',
            $path . $command_class_name. 'Test.php',
            $parameters,
            null,
            $renderer
        );

        $this->renderFile(
            'core/test/command_data_provider.php.twig',
            $path . $command_name. 'DataProviderTrait.php',
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
