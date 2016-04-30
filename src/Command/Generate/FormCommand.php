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
use Drupal\Console\Command\MenuTrait;
use Drupal\Console\Command\FormTrait;
use Drupal\Console\Generator\FormGenerator;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

abstract class FormCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use FormTrait;
    use MenuTrait;

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
            ->addOption('routing', '', InputOption::VALUE_NONE, $this->trans('commands.generate.form.options.routing'))
            ->addOption(
                'menu_link_gen',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.form.options.menu_link_gen')
            )
            ->addOption('menu_link_title', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.form.options.menu_link_title'))
            ->addOption('menu_parent', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.form.options.menu_parent'))
            ->addOption('menu_link_desc', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.form.options.menu_link_desc'));
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
        $menu_link_gen = $input->getOption('menu_link_gen');
        $menu_parent = $input->getOption('menu_parent');
        $menu_link_title = $input->getOption('menu_link_title');
        $menu_link_desc = $input->getOption('menu_link_desc');

        // if exist form generate config file
        $inputs = $input->getOption('inputs');
        $build_services = $this->buildServices($services);

        $this
            ->getGenerator()
            ->generate($module, $class_name, $form_id, $form_type, $build_services, $inputs, $update_routing, $menu_link_gen, $menu_link_title, $menu_parent, $menu_link_desc);

        $this->getChain()->addCommand('router:rebuild');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

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
            $className = $io->ask(
                $this->trans('commands.generate.form.questions.class'),
                'DefaultForm'
            );
            $input->setOption('class', $className);
        }

        // --form-id option
        $formId = $input->getOption('form-id');
        if (!$formId) {
            $formId = $io->ask(
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
                $routing = $io->confirm(
                    $this->trans('commands.generate.form.questions.routing'),
                    true
                );
                $input->setOption('routing', $routing);
            }
        }

        // --link option for links.menu
        if ($this->formType == 'ConfigFormBase') {
            $menu_options = $this->menuQuestion($output, $className);
            $menu_link_gen = $input->getOption('menu_link_gen');
            $menu_link_title = $input->getOption('menu_link_title');
            $menu_parent = $input->getOption('menu_parent');
            $menu_link_desc = $input->getOption('menu_link_desc');
            if (!$menu_link_gen || !$menu_link_title || !$menu_parent || !$menu_link_desc) {
                $input->setOption('menu_link_gen', $menu_options['menu_link_gen']);
                $input->setOption('menu_link_title', $menu_options['menu_link_title']);
                $input->setOption('menu_parent', $menu_options['menu_parent']);
                $input->setOption('menu_link_desc', $menu_options['menu_link_desc']);
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
