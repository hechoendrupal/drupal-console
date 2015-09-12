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
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.command.options.class')
            )
            ->addOption(
                'name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.command.options.name')
            )
            ->addOption(
                'container-aware',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.command.options.container-aware')
            );
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
        $class = $input->getOption('class');
        $name = $input->getOption('name');
        $containerAware = $input->getOption('container-aware');

        $this
            ->getGenerator()
            ->generate($module, $name, $class, $containerAware);
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
        $name = $input->getOption('name');
        if (!$name) {
            $name = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.command.questions.name'), $module.':default'),
                $module.':default'
            );
        }
        $input->setOption('name', $name);

        // --class-name option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.command.questions.class'), 'DefaultCommand'),
                function ($class) {
                    return $this->validateClassName($class);
                },
                false,
                'DefaultCommand',
                null
            );
            $input->setOption('class', $class);
        }

        // --container option
        $containerAware = $input->getOption('container-aware');
        if (!$containerAware && $dialog->askConfirmation(
            $output,
            $dialog->getQuestion($this->trans('commands.generate.command.questions.container-aware'), 'yes', '?'),
            true
        )
        ) {
            $containerAware = true;
        }
        $input->setOption('container-aware', $containerAware);
    }

    protected function createGenerator()
    {
        return new CommandGenerator();
    }
}
