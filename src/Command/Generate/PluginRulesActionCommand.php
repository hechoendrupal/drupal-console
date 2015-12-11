<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRulesActionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginRulesActionGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginRulesActionCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:rulesaction')
            ->setDescription($this->trans('commands.generate.plugin.rulesaction.description'))
            ->setHelp($this->trans('commands.generate.plugin.rulesaction.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id')
            )
            ->addOption('type', '', InputOption::VALUE_REQUIRED, $this->trans('commands.generate.plugin.rulesaction.options.type'))
            ->addOption(
                'category',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rulesaction.options.category')
            )
            ->addOption(
                'context',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.context')
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
        $type = $input->getOption('type');
        $category = $input->getOption('category');
        $context = $input->getOption('context');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $category, $context, $type);

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
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $output->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.class'),
                'DefaultAction'
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $output->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.label'),
                $this->getStringHelper()->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $output->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id'),
                $this->getStringHelper()->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --type option
        $type = $input->getOption('type');
        if (!$type) {
            $type = $output->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.type'),
                'user'
            );
            $input->setOption('type', $type);
        }

        // --category option
        $category = $input->getOption('category');
        if (!$category) {
            $category = $output->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.category'),
                $this->getStringHelper()->camelCaseToUnderscore($class_name)
            );
            $input->setOption('category', $category);
        }

        // --context option
        $context = $input->getOption('context');
        if (!$context) {
            $context = $output->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.context'),
                $this->getStringHelper()->camelCaseToUnderscore($class_name)
            );
            $input->setOption('context', $context);
        }
    }

    protected function createGenerator()
    {
        return new PluginRulesActionGenerator();
    }
}
