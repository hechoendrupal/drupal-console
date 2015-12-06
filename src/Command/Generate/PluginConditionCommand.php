<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorPluginFieldFormatterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\Console\Generator\PluginConditionGenerator;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginConditionCommand extends GeneratorCommand
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
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.condition.options.class')
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
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $context_definition_id = $input->getOption('context-definition-id');
        $context_definition_label = $input->getOption('context-definition-label');
        $context_definition_required = $input->getOption('context-definition-required')?'TRUE':'FALSE';

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $context_definition_id, $context_definition_label, $context_definition_required);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $entity_manager = $this->getEntityManager();

        $entity_types = $entity_manager->getEntityTypeLabels(true);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
        }
        $input->setOption('module', $module);

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $output->ask(
                $this->trans('commands.generate.plugin.condition.questions.class'),
                'ExampleCondition'
            );
            $input->setOption('class', $class);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $output->ask(
                $this->trans('commands.generate.plugin.condition.questions.label'),
                $this->getStringHelper()->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $output->ask(
                $this->trans('commands.generate.plugin.condition.questions.plugin-id'),
                $this->getStringHelper()->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

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
            $context_definition_label = $output->ask(
                $this->trans('commands.generate.plugin.condition.questions.context-definition-label'),
                $context_definition_id_value?:null
            );
            $input->setOption('context-definition-label', $context_definition_label);
        }

        $context_definition_required = $input->getOption('context-definition-required');
        if (empty($context_definition_required)) {
            $context_definition_required = $output->confirm(
                $this->trans('commands.generate.plugin.condition.questions.context-definition-required'),
                true
            );
            $input->setOption('context-definition-required', $context_definition_required);
        }
    }

    protected function createGenerator()
    {
        return new PluginConditionGenerator();
    }
}
