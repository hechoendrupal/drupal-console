<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginTypeYamlCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Generator\PluginTypeYamlGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\FormTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class GeneratorPluginTypeYamlCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:type:yaml')
            ->setDescription($this->trans('commands.generate.plugin.type.yaml.description'))
            ->setHelp($this->trans('commands.generate.plugin.type.yaml.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.yaml.options.class-name')
            )
            ->addOption(
                'plugin-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-name')
            )
            ->addOption(
                'plugin-file-name',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-file-name')
            );
        }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $plugin_name = $input->getOption('plugin-name');
        $plugin_file_name = $input->getOption('plugin-file-name');

        $generator = $this->getGenerator();
        $generator->generate($module, $class_name, $plugin_name, $plugin_file_name);
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
                    $this->trans('commands.generate.plugin.type.yaml.options.class-name'),
                    'ExamplePlugin'
                ),
                'ExamplePlugin'
            );
        }
        $input->setOption('class-name', $class_name);

        $default_plugin_name = $this->getStringUtils()->camelCaseToUnderscore($class_name);

        // --plugin-name option
        $plugin_name = $input->getOption('plugin-name');
        if (!$plugin_name) {
            $plugin_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.type.yaml.options.plugin-name'),
                    $default_plugin_name
                ),
                $default_plugin_name
            );
        }
        $input->setOption('plugin-name', $plugin_name);

        $default_file_name = strtr($plugin_name, '_-', '..');

        // --plugin-file-name option
        $plugin_file_name = $input->getOption('plugin-file-name');
        if (!$plugin_file_name) {
            $plugin_file_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.type.yaml.options.plugin-file-name'),
                    $default_file_name
                ),
                $default_file_name
            );
        }
        $input->setOption('plugin-file-name', $plugin_file_name);
    }

    protected function createGenerator()
    {
        return new PluginTypeYamlGenerator();
    }
}
