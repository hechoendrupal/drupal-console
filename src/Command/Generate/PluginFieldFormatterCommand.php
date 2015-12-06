<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldFormatterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldFormatterGenerator;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginFieldFormatterCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldformatter')
            ->setDescription($this->trans('commands.generate.plugin.fieldformatter.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldformatter.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldformatter.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldformatter.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldformatter.options.plugin-id')
            )
            ->addOption(
                'field-type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldformatter.options.field-type')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $field_type = $input->getOption('field-type');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $field_type);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
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
        $class = $input->getOption('class');
        if (!$class) {
            $class = $output->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.class'),
                'ExampleFieldFormatter'
            );
            $input->setOption('class', $class);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $output->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.label'),
                $this->getStringHelper()->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --name option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $output->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.plugin-id'),
                $this->getStringHelper()->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --field type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            $field_type = $output->ask(
                $this->trans('commands.generate.plugin.fieldformatter.questions.field-type')
            );
            $input->setOption('field-type', $field_type);
        }
    }

    protected function createGenerator()
    {
        return new PluginFieldFormatterGenerator();
    }
}
