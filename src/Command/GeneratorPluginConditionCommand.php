<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginFieldFormatterCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\AppConsole\Generator\PluginConditionGenerator;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPluginConditionCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:condition')
            ->setDescription($this->trans('commands.generate.plugin.condition.description'))
            ->setHelp($this->trans('commands.generate.plugin.condition.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.class-name')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.plugin-id')
            )
            ->addOption(
                'context-definition-id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.context-definition-id')
            )
            ->addOption(
                'context-definition-label',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.context-definition-label')
            )
            ->addOption(
                'context-definition-required',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.condition.options.context-definition-required')
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
        $context_definition_id = $input->getOption('context-definition-id');
        $context_definition_label = $input->getOption('context-definition-label');
        $context_definition_required = $input->getOption('context-definition-required')?'TRUE':'FALSE';

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $context_definition_id, $context_definition_label, $context_definition_required);

        $this->getHelper('chain')->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $context_repository = $this->getContextRepository();

        $entity_manager = $this->getEntityManager();

        $entity_types = $entity_manager->getEntityTypeLabels(true);

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
                    $this->trans('commands.generate.plugin.condition.questions.class-name'),
                    'ExampleCondition'
                ),
                'ExampleCondition'
            );
        }
        $input->setOption('class-name', $class_name);

        $default_label = $this->getStringUtils()->camelCaseToHuman($class_name);

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.condition.questions.label'), $default_label),
                $default_label
            );
        }
        $input->setOption('label', $label);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --name option
        $plugin_id = $input->getOption('plugin-id');

        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.condition.questions.plugin-id'),
                    $machine_name
                ),
                $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        $context_definition_id = $input->getOption('context-definition-id');
        if (!$context_definition_id) {
            $questionHelper = $this->getQuestionHelper();

            $context_type = array('language' => 'Language', "entity" => "Entity");
            $question = new ChoiceQuestion(
                $this->trans('commands.generate.plugin.condition.questions.context-type'),
                $context_type,
                current($context_type)
            );
            $context_type_sel = $questionHelper->ask($input, $output, $question);

            if ($context_type_sel == 'language') {
                $context_definition_id = $context_type_sel;
                $context_definition_id_value = ucfirst($context_type_sel);
            } else {
                $options = array_keys($entity_types);
                $options = array_combine($options, $options);
                $question = new ChoiceQuestion(
                    $this->trans('commands.generate.plugin.condition.questions.context-entity-type'),
                    $options,
                    current($options)
                );
                $content_entity_types_sel = $questionHelper->ask($input, $output, $question);

                $options = $entity_types[$content_entity_types_sel];
                $options = array_combine($options, $options);
                $question = new ChoiceQuestion(
                    $this->trans('commands.generate.plugin.condition.questions.context-definition-id'),
                    $options,
                    current($options)
                );
                $context_definition_id_sel = $questionHelper->ask($input, $output, $question);

                $context_definition_id_value = array_search($context_definition_id_sel, $entity_types[$content_entity_types_sel]);

                $context_definition_id = 'entity:' . $context_definition_id_value;
            }
            $input->setOption('context-definition-id', $context_definition_id);
        }

        $context_definition_label = $input->getOption('context-definition-label');
        if (!$context_definition_label) {
            $context_definition_label = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.condition.questions.context-definition-label'), $context_definition_id_value),
                $context_definition_id_value
            );
        }
        $input->setOption('context-definition-label', $context_definition_label);

        $context_definition_required = $input->getOption('context-definition-required');
        if (empty($context_definition_required)) {
            $context_definition_required = $dialog->askConfirmation(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.condition.questions.context-definition-required'), 'yes', '?'),
                true
            );
        }

        $input->setOption('context-definition-required', $context_definition_required);
    }

    protected function createGenerator()
    {
        return new PluginConditionGenerator();
    }
}
