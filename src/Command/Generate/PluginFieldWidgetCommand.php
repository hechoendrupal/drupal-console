<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldWidgetCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginFieldWidgetGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginFieldWidgetCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:fieldwidget')
            ->setDescription($this->trans('commands.generate.plugin.fieldwidget.description'))
            ->setHelp($this->trans('commands.generate.plugin.fieldwidget.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.fieldwidget.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.plugin-id')
            )
            ->addOption(
                'field-type',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.fieldwidget.options.field-type')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
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
        $io = new DrupalStyle($input, $output);
        $fieldTypePluginManager = $this->getService('plugin.manager.field.field_type');

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.plugin.fieldwidget.questions.class'),
                'ExampleFieldWidget'
            );
            $input->setOption('class', $class_name);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.fieldwidget.questions.label'),
                $this->getStringHelper()->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.fieldwidget.questions.plugin-id'),
                $this->getStringHelper()->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --field-type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            // Gather valid field types.
            $field_type_options = array();
            foreach ($fieldTypePluginManager->getGroupedDefinitions($fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
                foreach ($field_types as $name => $field_type) {
                    $field_type_options[] = $name;
                }
            }

            $field_type  = $io->choice(
                $this->trans('commands.generate.plugin.fieldwidget.questions.field-type'),
                $field_type_options
            );

            $input->setOption('field-type', $field_type);
        }
    }

    protected function createGenerator()
    {
        return new PluginFieldWidgetGenerator();
    }
}
