<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\FormCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

abstract class FormCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use FormTrait;

    private $formType;
    private $commandName;

    protected function setFormType($formType)
    {
        return $this->formType = $formType;
    }

    protected function setCommandName($commandName)
    {
        return $this->commandName = $commandName;
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription(
                sprintf(
                    $this->trans('commands.generate.form.description'),
                    $this->formType
                )
            )
            ->setHelp(
                sprintf(
                    $this->trans('commands.generate.form.help'),
                    $this->commandName,
                    $this->formType
                )
            )
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.class')
            )
            ->addOption(
                'form-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.form-id')
            )
            ->addOption('services', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.services'))
            ->addOption('inputs', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.inputs'))
            ->addOption('routing', '', InputOption::VALUE_NONE, $this->trans('commands.generate.form.options.routing'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $services = $input->getOption('services');
        $update_routing = $input->getOption('routing');
        $class_name = $input->getOption('class');
        $form_id = $input->getOption('form-id');
        $form_type = $this->formType;

        // if exist form generate config file
        $inputs = $input->getOption('inputs');
        $build_services = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $class_name, $form_id, $form_type, $build_services, $inputs, $update_routing);

        $this->getChain()->addCommand('router:rebuild');
    }

    /**
     * {@inheritdoc}
     */
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
        $className = $input->getOption('class');
        if (!$className) {
            $className = $output->ask(
                $this->trans('commands.generate.form.questions.class'),
                'DefaultForm'
            );
            $input->setOption('class', $className);
        }

        // --form-id option
        $formId = $input->getOption('form-id');
        if (!$formId) {
            $formId = $output->ask(
                $this->trans('commands.generate.form.questions.form-id'),
                $this->getStringHelper()->camelCaseToMachineName($className)
            );
            $input->setOption('form-id', $formId);
        }

        // --services option
        // @see use Drupal\Console\Command\ServicesTrait::servicesQuestion
        $services = $this->servicesQuestion($output);
        $input->setOption('services', $services);

        // --inputs option
        $inputs = $input->getOption('inputs');
        if (!$inputs) {
            // @see \Drupal\Console\Command\FormTrait::formQuestion
            $inputs = $this->formQuestion($output);
            $input->setOption('inputs', $inputs);
        }

        // --routing option for ConfigFormBase
        if ($this->formType == 'ConfigFormBase') {
            $routing = $input->getOption('routing');
            if (!$routing) {
                $routing = $output->confirm(
                    $this->trans('commands.generate.form.questions.routing'),
                    true
                );
                $input->setOption('routing', $routing);
            }
        }
    }

    /**
     * @return \Drupal\Console\Generator\FormGenerator.
     */
    protected function createGenerator()
    {
        return new FormGenerator();
    }
}
