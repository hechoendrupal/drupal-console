<?php
/**
 * @file
 * Containt Drupal\AppConsole\Command\GeneratorFormCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\FormTrait;
use Drupal\AppConsole\Generator\FormGenerator;

abstract class GeneratorFormCommand extends GeneratorCommand
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
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, $this->trans('common.options.module')),
        new InputOption('class-name','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.form.options.class-name')),
        new InputOption('form-id','',InputOption::VALUE_OPTIONAL, $this->trans('command.generate.form.options.form-id')),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, $this->trans('common.options.services')),
        new InputOption('inputs','',InputOption::VALUE_OPTIONAL, $this->trans('common.options.inputs')),
        new InputOption('routing', '', InputOption::VALUE_NONE, $this->trans('command.generate.form.options.routing')),
      ))
      ->setName($this->commandName)
      ->setDescription(
      sprintf(
        $this->trans('command.generate.form.description'),
        $this->formType
      )
      )
      ->setHelp(
        sprintf(
          $this->trans('command.generate.form.help'),
          $this->commandName,
          $this->formType
        )
      )
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $services = $input->getOption('services');
    $update_routing = $input->getOption('routing');
    $class_name = $input->getOption('class-name');
    $form_id = $input->getOption('form-id');

    // if exist form generate config file
    $inputs = $input->getOption('inputs');
    $build_services = $this->buildServices($services);

    $this
      ->getGenerator()
      ->generate($module, $class_name, $form_id, $build_services, $inputs, $update_routing);
  }

  /**
   * {@inheritdoc}
   */
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
        $dialog->getQuestion($this->trans('command.generate.form.questions.class-name'), 'DefaultForm'),
        'DefaultForm'
      );
    }
    $input->setOption('class-name', $class_name);

    // --form-id option
    $form_id = $input->getOption('form-id');
    if (!$form_id) {
      $form_id = $this->getStringUtils()->camelCaseToMachineName($class_name);
      $form_id = $dialog->ask(
        $output,
        $dialog->getQuestion($this->trans('command.generate.form.questions.form-id'), $form_id),
        $form_id
      );
    }
    $input->setOption('form-id', $form_id);

    // --services option
    // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::servicesQuestion
    $services_collection = $this->servicesQuestion($output, $dialog);
    $input->setOption('services', $services_collection);

    // --inputs option
    $inputs = $input->getOption('inputs');
    if (!$inputs) {
      // @see \Drupal\AppConsole\Command\Helper\FormTrait::formQuestion
      $inputs = $this->formQuestion($output, $dialog);
    }
    $input->setOption('inputs', $inputs);

    // --routing option
    $routing = $input->getOption('routing');
    if (!$routing && $dialog->askConfirmation(
      $output,
      $dialog->getQuestion($this->trans('command.generate.form.questions.routing'), 'yes', '?'),
      true)
    ) {
        $routing = true;
    }
    $input->setOption('routing', $routing);
  }

  /**
   * @return \Drupal\AppConsole\Generator\FormGenerator.
   */
  protected function createGenerator()
  {
    return new FormGenerator();
  }
}
