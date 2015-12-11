<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeAnnotationCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeAnnotationGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginTypeAnnotationCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:type:annotation')
            ->setDescription($this->trans('commands.generate.plugin.type.annotation.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.annotation.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.class')
            )
            ->addOption(
                'machine-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.plugin-id')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.annotation.options.label')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $machine_name = $input->getOption('machine-name');
        $label = $input->getOption('label');

        $generator = $this->getGenerator();
        $generator->generate($module, $class_name, $machine_name, $label);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $output->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.class'),
                'ExamplePlugin'
            );
            $input->setOption('class', $class_name);
        }

        // --machine-name option
        $machine_name = $input->getOption('machine-name');
        if (!$machine_name) {
            $machine_name = $output->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.machine-name'),
                $this->getStringHelper()->camelCaseToUnderscore($class_name)
            );
            $input->setOption('machine-name', $machine_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $output->ask(
                $this->trans('commands.generate.plugin.type.annotation.options.label'),
                $this->getStringHelper()->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }
    }

    protected function createGenerator()
    {
        return new PluginTypeAnnotationGenerator();
    }
}
