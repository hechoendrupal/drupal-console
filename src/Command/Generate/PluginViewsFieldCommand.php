<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginViewsFieldCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginViewsFieldGenerator;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginViewsFieldCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:views:field')
            ->setDescription($this->trans('commands.generate.plugin.views.field.description'))
            ->setHelp($this->trans('commands.generate.plugin.views.field.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.views.field.options.class')
            )
            ->addOption(
                'title',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.views.field.options.title')
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.views.field.options.description')
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
        $class_machine_name = $this->getStringHelper()->camelCaseToUnderscore($class_name);
        $title = $input->getOption('title');
        $description = $input->getOption('description');

        $this
            ->getGenerator()
            ->generate($module, $class_machine_name, $class_name, $title, $description);

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
                $this->trans('commands.generate.plugin.views.field.questions.class'),
                'CustomViewsField'
            );
        }
        $input->setOption('class', $class_name);

        // --title option
        $title = $input->getOption('title');
        if (!$title) {
            $title = $output->ask(
                $this->trans('commands.generate.plugin.views.field.questions.title'),
                $this->getStringHelper()->camelCaseToHuman($class_name)
            );
            $input->setOption('title', $title);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $output->ask(
                $this->trans('commands.generate.plugin.views.field.questions.description'),
                $this->trans('commands.generate.plugin.views.field.questions.description_default')
            );
            $input->setOption('description', $description);
        }
    }

    protected function createGenerator()
    {
        return new PluginViewsFieldGenerator();
    }
}
