<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginFieldFormatterCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\PluginFieldFormatterGenerator;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPluginFieldFormatterCommand extends GeneratorCommand
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
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldformatter.options.class-name')
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
        $dialog = $this->getDialogHelper();

        // @see use Drupal\AppConsole\Command\Helper\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $field_type = $input->getOption('field-type');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $field_type);

// @todo Fails with InvalidArgumentException
//        $this->getHelper('chain')->addCommand('cache:rebuild', ['--cache' => 'discovery']);
    }

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

        // --class-name option
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldformatter.questions.class-name'),
                    'ExampleFieldFormatter'
                ),
                'ExampleFieldFormatter'
            );
        }
        $input->setOption('class-name', $class_name);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.fieldformatter.questions.label'), $machine_name),
                $machine_name
            );
        }
        $input->setOption('label', $label);

        // --name option
        $plugin_id = $input->getOption('plugin-id');

        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldformatter.questions.plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        // --field type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldformatter.questions.field-type'),
                    ''
                ),
                ''
            );
        }
        $input->setOption('field-type', $field_type);
    }

    protected function createGenerator()
    {
        return new PluginFieldFormatterGenerator();
    }
}
