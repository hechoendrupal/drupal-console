<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginTypeYamlCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginTypeYamlGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginTypeYamlCommand extends GeneratorCommand
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
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.type.yaml.options.class')
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
        $class_name = $input->getOption('class');
        $plugin_name = $input->getOption('plugin-name');
        $plugin_file_name = $input->getOption('plugin-file-name');

        $generator = $this->getGenerator();
        $generator->generate($module, $class_name, $plugin_name, $plugin_file_name);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

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
                $this->trans('commands.generate.plugin.type.yaml.options.class'),
                'ExamplePlugin'
            );
            $input->setOption('class', $class_name);
        }

        // --plugin-name option
        $plugin_name = $input->getOption('plugin-name');
        if (!$plugin_name) {
            $plugin_name = $io->ask(
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-name'),
                $this->getStringHelper()->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-name', $plugin_name);
        }

        // --plugin-file-name option
        $plugin_file_name = $input->getOption('plugin-file-name');
        if (!$plugin_file_name) {
            $plugin_file_name = $io->ask(
                $this->trans('commands.generate.plugin.type.yaml.options.plugin-file-name'),
                strtr($plugin_name, '_-', '..')
            );
            $input->setOption('plugin-file-name', $plugin_file_name);
        }
    }

    protected function createGenerator()
    {
        return new PluginTypeYamlGenerator();
    }
}
