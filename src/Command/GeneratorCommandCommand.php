<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorCommandCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\Helper\ConfirmationTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\CommandGenerator;

class GeneratorCommandCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('generate:command')
          ->setDescription($this->trans('commands.generate.command.description'))
          ->setHelp($this->trans('commands.generate.command.help'))
          ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
          ->addOption('class-name', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.command.options.class-name'))
          ->addOption('command', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.command.options.command'))
          ->addOption('container', '', InputOption::VALUE_NONE,
            $this->trans('commands.generate.command.options.container'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $command = $input->getOption('command');
        $container = $input->getOption('container');

        $this
          ->getGenerator()
          ->generate($module, $command, $class_name, $container);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --command
        $command = $input->getOption('command');
        if (!$command) {
            $command = $dialog->ask($output,
              $dialog->getQuestion($this->trans('commands.generate.command.questions.command'), $module . ':default'),
              $module . ':default'
            );
        }
        $input->setOption('command', $command);

        // --name option
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $dialog->ask($output,
              $dialog->getQuestion($this->trans('commands.generate.command.questions.class-name'), 'DefaultCommand'),
              'DefaultCommand'
            );
        }
        $input->setOption('class-name', $class_name);

        // --container option
        $container = $input->getOption('container');
        if (!$container && $dialog->askConfirmation($output,
            $dialog->getQuestion($this->trans('commands.generate.command.questions.container'), 'yes', '?'),
            true)
        ) {
            $container = true;
        }
        $input->setOption('container', $container);
    }

    protected function createGenerator()
    {
        return new CommandGenerator();
    }
}
