<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorPluginFieldFormatterCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginViewsFieldGenerator;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\ConfirmationTrait;

class GeneratorPluginViewsFieldCommand extends GeneratorCommand
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
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.views.field.options.class-name')
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
        $dialog = $this->getDialogHelper();

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmationQuestion
        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
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
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --class-name option
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin.views.field.questions.class-name'),
                    'CustomViewsField'
                ),
                'CustomViewsField'
            );
        }
        $input->setOption('class-name', $class_name);

        $default_label = $this->getStringHelper()->camelCaseToHuman($class_name);

        // --plugin title option
        $title = $input->getOption('title');
        if (!$title) {
            $title = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.views.field.questions.title'), $default_label),
                $default_label
            );
        }
        $input->setOption('title', $title);

        // --plugin title option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $dialog->ask(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.plugin.views.field.questions.description'), $this->trans('commands.generate.plugin.views.field.questions.description_default')),
                $this->trans('commands.generate.plugin.views.field.questions.description_default')
            );
        }
        $input->setOption('description', $description);
    }

    protected function createGenerator()
    {
        return new PluginViewsFieldGenerator();
    }
}
