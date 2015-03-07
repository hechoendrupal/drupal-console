<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginBlockCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Generator\PluginRulesActionGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\FormTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPluginRulesActionCommand extends GeneratorCommand
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
          ->addOption('class-name', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rulesaction.options.class-name'))
          ->addOption('label', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rulesaction.options.label'))
          ->addOption('plugin-id', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rulesaction.options.plugin-id'))
          ->addOption('category', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.generate.plugin.rulesaction.options.category'))
          ->addOption('context', '', InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rulesaction.options.context'));
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
        $category = $input->getOption('category');
        $context = $input->getOption('context');

        $this
          ->getGenerator()
          ->generate($module, $class_name, $label, $plugin_id, $category, $context);
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
              $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.class-name'),
                'DefaultBlock'),
              'DefaultBlock'
            );
        }
        $input->setOption('class-name', $class_name);

        $machine_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.label'), $machine_name),
              $machine_name
            );
        }
        $input->setOption('label', $label);

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.plugin-id'),
                $machine_name),
              $machine_name
            );
        }
        $input->setOption('plugin-id', $plugin_id);

        // --category option
        $category = $input->getOption('category');
        if (!$category) {
            $category = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.category'),
                $machine_name),
              $machine_name
            );
        }
        $input->setOption('category', $category);

        // --context option
        $context = $input->getOption('context');
        if (!$context) {
            $context = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.plugin.rulesaction.options.context'), $machine_name),
              $machine_name
            );
        }
        $input->setOption('context', $context);
    }

    protected function createGenerator()
    {
        return new PluginRulesActionGenerator();
    }
}
