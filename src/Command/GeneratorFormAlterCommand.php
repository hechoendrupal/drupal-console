<?php

/**
 * @file
 * Contains \Drupal\Console\Command\GeneratorPluginBlockCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\FormAlterGenerator;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Command\ConfirmationTrait;

class GeneratorFormAlterCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:form:alter')
            ->setDescription($this->trans('commands.generate.form.alter.description'))
            ->setHelp($this->trans('commands.generate.form.alter.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'form-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.alter.options.form-id')
            )
            ->addOption(
                'inputs',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
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
        $form_id = $input->getOption('form-id');
        $inputs = $input->getOption('inputs');


        $this
            ->getGenerator()
            ->generate($module, $form_id, $inputs);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $moduleHandler = $this->getModuleHandler();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --class-name option
        $form_id = $input->getOption('form-id');
        if (!$form_id) {
            $forms = [];
            if ($moduleHandler->moduleExists('webprofiler')) {
                $output->writeln('[-] <info>'.$this->trans('commands.generate.form.alter.messages.loading-forms').'</info>');
                $forms = $this->getWebprofilerForms();
            }

            $form_id = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.form.alter.options.form-id'), current(array_keys($forms))),
                function ($form) {
                    return $form;
                },
                false,
                '',
                array_combine(array_keys($forms), array_keys($forms))
            );
        }
        $input->setOption('form-id', $form_id);

        $output->writeln($this->trans('commands.generate.form.alter.messages.inputs'));

        // @see Drupal\Console\Command\FormTrait::formQuestion
        $form = $this->formQuestion($output, $dialog);
        $input->setOption('inputs', $form);
    }

    protected function createGenerator()
    {
        return new FormAlterGenerator();
    }
}
