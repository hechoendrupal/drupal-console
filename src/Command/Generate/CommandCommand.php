<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\CommandCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\CommandGenerator;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class CommandCommand extends GeneratorCommand
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
        $io = new DrupalStyle($input, $output);

        $module = $input->getOption('module');
        $class = $input->getOption('class');
        $name = $input->getOption('name');
        $containerAware = $input->getOption('container-aware');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this
            ->getGenerator()
            ->generate($module, $name, $class, $containerAware);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --name
        $name = $input->getOption('name');
        if (!$name) {
            $name = $io->ask(
                $this->trans('commands.generate.command.questions.name'),
                sprintf('%s:default', $module)
            );
            $input->setOption('name', $name);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.command.questions.class'),
                'DefaultCommand',
                function ($class) {
                    return $this->getValidator()->validateCommandName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --container-aware option
        $containerAware = $input->getOption('container-aware');
        if (!$containerAware) {
            $containerAware = $io->confirm(
                $this->trans('commands.generate.command.questions.container-aware'),
                true
            );
            $input->setOption('container-aware', $containerAware);
        }
    }

    protected function createGenerator()
    {
        return new CommandGenerator();
    }
}
