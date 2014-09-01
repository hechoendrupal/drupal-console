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

  protected function getFormType()
  {
    return $this->$formType;
  }

  protected function getCommandName()
  {
    return $this->$commandName;
  }

  protected function configure()
  {
    $formType = $this->getFormType();
    $commandName = $this->getCommandName();
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('class-name','',InputOption::VALUE_OPTIONAL, 'Form name'),
        new InputOption('form-id','',InputOption::VALUE_OPTIONAL, 'Form id'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
        new InputOption('inputs','',InputOption::VALUE_OPTIONAL, 'Create a inputs in a form'),
        new InputOption('routing', '', InputOption::VALUE_NONE, 'Update routing'),
      ))
      ->setDescription('Generate '. $formType)
      ->setHelp('The <info>'.$commandName.'</info> command helps you generate a new '. $formType)
      ->setName($commandName);
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

    $errors = '';
    $dialog->writeGeneratorSummary($output, $errors);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal form generator');

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
        $dialog->getQuestion('Enter the form name', 'DefaultForm'),
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
        $dialog->getQuestion('Enter the form id', $form_id),
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
      $dialog->getQuestion('Update routing file?', 'yes', '?'),
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
