<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginCKEditorButtonCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginCKEditorButtonGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginCKEditorButtonCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:ckeditorbutton')
            ->setDescription($this->trans('commands.generate.plugin.ckeditorbutton.description'))
            ->setHelp($this->trans('commands.generate.plugin.ckeditorbutton.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.plugin-id')
            )
            ->addOption(
                'button-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.button-name')
            )
            ->addOption(
                'button-icon-path',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.button-icon-path')
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
        $button_name = $input->getOption('button-name');
        $button_icon_path = $input->getOption('button-icon-path');

        $this
            ->getGenerator()
            ->generate($module, $class_name, $label, $plugin_id, $button_name, $button_icon_path);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery'], false);
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
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.class'),
                'DefaultCKEditorButton'
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.label'),
                $this->getStringHelper()->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.plugin-id'),
                $this->getStringHelper()->camelCaseToLowerCase($label)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --button-name option
        $button_name = $input->getOption('button-name');
        if (!$button_name) {
            $button_name = $io->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.button-name'),
                $this->getStringHelper()->anyCaseToUcFirst($plugin_id)
            );
            $input->setOption('button-name', $button_name);
        }

        // --button-icon-path option
        $button_icon_path = $input->getOption('button-icon-path');
        if (!$button_icon_path) {
            $button_icon_path = $io->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.button-icon-path'),
                drupal_get_path('module', $module) . '/js/plugins/' . $plugin_id . '/images/icon.png'
            );
            $input->setOption('button-icon-path', $button_icon_path);
        }
    }

    protected function createGenerator()
    {
        return new PluginCKEditorButtonGenerator();
    }
}
