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

class GeneratorPluginFieldCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:field')
            ->setDescription($this->trans('commands.generate.plugin.field.description'))
            ->setHelp($this->trans('commands.generate.plugin.field.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'type-class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.type-class-name')
            )
            ->addOption(
                'type-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-label')
            )
            ->addOption(
                'type-plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-plugin-id')
            )
            ->addOption(
                'type-description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-type-description')
            )
            ->addOption(
                'formatter-class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.class-name')
            )
            ->addOption(
                'formatter-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.formatter-label')
            )
            ->addOption(
                'formatter-plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.formatter-plugin-id')
            )
            ->addOption(
                'widget-class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.formatter-class-name')
            )
            ->addOption(
                'widget-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.widget-label')
            )
            ->addOption(
                'widget-plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.widget-plugin-id')
            )
            ->addOption(
                'field-type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.field-type')
            )
            ->addOption(
                'default-widget',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.default-widget')
            )
            ->addOption(
                'default-formatter',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.default-formatter')
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

        $this
            ->getHelper('chain')
            ->addCommand(
                'generate:plugin:fieldtype', [
                '--module' => $input->getOption('module'),
                '--class-name' => $input->getOption('type-class-name'),
                '--label' => $input->getOption('type-label'),
                '--plugin-id' => $input->getOption('type-plugin-id'),
                '--description' => $input->getOption('type-description'),
                '--default-widget' => $input->getOption('default-widget'),
                '--default-formatter' => $input->getOption('default-formatter'),
                ],
                false
            );
        $this
            ->getHelper('chain')
            ->addCommand(
                'generate:plugin:fieldwidget', [
                '--module' => $input->getOption('module'),
                '--class-name' => $input->getOption('widget-class-name'),
                '--label' => $input->getOption('widget-label'),
                '--plugin-id' => $input->getOption('widget-plugin-id'),
                '--field-type' => $input->getOption('field-type'),
                ],
                false
            );
        $this
            ->getHelper('chain')
            ->addCommand(
                'generate:plugin:fieldformatter', [
                '--module' => $input->getOption('module'),
                '--class-name' => $input->getOption('formatter-class-name'),
                '--label' => $input->getOption('formatter-label'),
                '--plugin-id' => $input->getOption('formatter-plugin-id'),
                '--field-type' => $input->getOption('field-type'),
                ],
                false
            );

        // @todo Fails with InvalidArgumentException
        //        $this->getHelper('chain')->addCommand('cache:rebuild', ['--cache' => 'discovery'], false);
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

        // --type-class-name option
        $class_name = $input->getOption('type-class-name');
        if (!$class_name) {
            $class_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.type-class-name'),
                    'ExampleFieldType'
                ),
                'ExampleFieldType'
            );
        }
        $input->setOption('type-class-name', $class_name);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --type-label option
        $label = $input->getOption('type-label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.field.questions.type-label'), $machine_name),
                $machine_name
            );
        }
        $input->setOption('type-label', $label);

        $default_label = $this->getStringUtils()->camelCaseToHuman($class_name);

        // --type-plugin-id option
        $plugin_id = $input->getOption('type-plugin-id');

        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.type-plugin-id'),
                    $default_label
                ),
                $default_label
            );
        }
        $input->setOption('type-plugin-id', $plugin_id);

        // --type-description option
        $description = $input->getOption('type-description');
        if (!$description) {
            $description = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.type-description'),
                    'My Field Type'
                ),
                'My Field Type'
            );
        }
        $input->setOption('type-description', $description);

        // --widget-class-name option
        $class_name = $input->getOption('widget-class-name');
        if (!$class_name) {
            $class_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.widget-class-name'),
                    'ExampleWidgetType'
                ),
                'ExampleWidgetType'
            );
        }
        $input->setOption('widget-class-name', $class_name);

        $default_label = $this->getStringUtils()->camelCaseToHuman($class_name);

        // --widget-label option
        $label = $input->getOption('widget-label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.field.questions.widget-label'), $default_label),
                $default_label
            );
        }
        $input->setOption('widget-label', $label);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --widget-plugin-id option
        $plugin_id = $input->getOption('widget-plugin-id');

        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.widget-plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('widget-plugin-id', $plugin_id);

        // --formatter-class-name option
        $class_name = $input->getOption('formatter-class-name');
        if (!$class_name) {
            $class_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.formatter-class-name'),
                    'ExampleFormatterType'
                ),
                'ExampleFormatterType'
            );
        }
        $input->setOption('formatter-class-name', $class_name);

        $default_label = $this->getStringUtils()->camelCaseToHuman($class_name);

        // --formatter-label option
        $label = $input->getOption('formatter-label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.field.questions.formatter-label'), $default_label),
                $default_label
            );
        }
        $input->setOption('formatter-label', $label);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --formatter-plugin-id option
        $plugin_id = $input->getOption('formatter-plugin-id');

        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.formatter-plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('formatter-plugin-id', $plugin_id);

        // --field-type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.field-type'),
                    ''
                ),
                ''
            );
        }
        $input->setOption('field-type', $field_type);

        // --default-widget option
        $field_type = $input->getOption('default-widget');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.default-widget'),
                    $input->getOption('widget-plugin-id')
                ),
                $input->getOption('widget-plugin-id')
            );
        }
        $input->setOption('default-widget', $field_type);

        // --default-formatter option
        $field_type = $input->getOption('default-formatter');
        if (!$field_type) {
            $field_type = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.field.questions.default-formatter'),
                    $input->getOption('formatter-plugin-id')
                ),
                $input->getOption('formatter-plugin-id')
            );
        }
        $input->setOption('default-formatter', $field_type);
    }

    protected function createGenerator()
    {
        return new PluginFieldTypeGenerator();
    }
}
