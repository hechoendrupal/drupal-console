<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorPluginFieldFormatterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginConditionGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
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
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
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
        $io = new DrupalStyle($input, $output);

        $entityTypeRepository = $this->getService('entity_type.repository');

        $entity_types = $entityTypeRepository->getEntityTypeLabels(true);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
        }
        $input->setOption('module', $module);

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.class'),
                'ExampleCondition'
            );
            $input->setOption('class', $class);
        }

        // --plugin label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.label'),
                $this->getStringHelper()->camelCaseToHuman($class)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $pluginId = $input->getOption('plugin-id');
        if (!$pluginId) {
            $pluginId = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.plugin-id'),
                $this->getStringHelper()->camelCaseToUnderscore($class)
            );
            $input->setOption('plugin-id', $pluginId);
        }

        $context_definition_id = $input->getOption('context-definition-id');
        if (!$context_definition_id) {
            $context_type = array('language' => 'Language', "entity" => "Entity");
            $context_type_sel = $io->choice(
                $this->trans('commands.generate.plugin.condition.questions.context-type'),
                array_values($context_type)
            );
            $context_type_sel = array_search($context_type_sel, $context_type);

            if ($context_type_sel == 'language') {
                $context_definition_id = $context_type_sel;
                $context_definition_id_value = ucfirst($context_type_sel);
            } else {
                $content_entity_types_sel = $io->choice(
                    $this->trans('commands.generate.plugin.condition.questions.context-entity-type'),
                    array_keys($entity_types)
                );

                $contextDefinitionIdList = $entity_types[$content_entity_types_sel];
                $context_definition_id_sel = $io->choice(
                    $this->trans('commands.generate.plugin.condition.questions.context-definition-id'),
                    array_values($contextDefinitionIdList)
                );

                $context_definition_id_value = array_search(
                    $context_definition_id_sel,
                    $contextDefinitionIdList
                );

                $context_definition_id = 'entity:' . $context_definition_id_value;
            }
            $input->setOption('context-definition-id', $context_definition_id);
        }

        $context_definition_label = $input->getOption('context-definition-label');
        if (!$context_definition_label) {
            $context_definition_label = $io->ask(
                $this->trans('commands.generate.plugin.condition.questions.context-definition-label'),
                $context_definition_id_value?:null
            );
            $input->setOption('context-definition-label', $context_definition_label);
        }

        $context_definition_required = $input->getOption('context-definition-required');
        if (empty($context_definition_required)) {
            $context_definition_required = $io->confirm(
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
