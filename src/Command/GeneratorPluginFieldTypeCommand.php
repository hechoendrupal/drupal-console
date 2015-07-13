<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginFieldTypeCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\PluginFieldTypeGenerator;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPluginFieldTypeCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldtype')
            ->setDescription($this->trans('commands.generate.plugin.fieldtype.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldtype.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldtype.options.class-name')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.plugin-id')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.description')
            )
            ->addOption(
                'default-widget',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.default-widget')
            )
            ->addOption(
                'default-formatter',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldtype.options.default-formatter')
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
        $description = $input->getOption('description');
        $default_widget = $input->getOption('default-widget');
        $default_formatter = $input->getOption('default-formatter');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $description, $default_widget, $default_formatter);

        $this->getHelper('chain')->addCommand('cache:rebuild', ['--cache' => 'discovery']);
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
                    $this->trans('commands.generate.plugin.fieldtype.questions.class-name'),
                    'ExampleFieldType'
                ),
                'ExampleFieldType'
            );
        }
        $input->setOption('class-name', $class_name);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.fieldtype.questions.label'), $machine_name),
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
                    $this->trans('commands.generate.plugin.fieldtype.questions.plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        // --description option
        $field_type = $input->getOption('description');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldtype.questions.description'),
                    'My Field Type'
                ),
                'My Field Type'
            );
        }
        $input->setOption('description', $field_type);

        // --default widget option
        $field_type = $input->getOption('default-widget');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldtype.questions.default-widget'),
                    ''
                ),
                ''
            );
        }
        $input->setOption('default-widget', $field_type);

        // --default formatter option
        $field_type = $input->getOption('default-formatter');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.fieldtype.questions.default-formatter'),
                    ''
                ),
                ''
            );
        }
        $input->setOption('default-formatter', $field_type);
    }

    protected function createGenerator()
    {
        return new PluginFieldTypeGenerator();
    }
}
