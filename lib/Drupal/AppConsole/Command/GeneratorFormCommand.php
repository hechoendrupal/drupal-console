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
use Drupal\AppConsole\Command\Helper\FormTrait;
use Drupal\AppConsole\Generator\FormGenerator;
use Drupal\AppConsole\Utils\Utils;

class GeneratorFormCommand extends GeneratorCommand
{
  use FormTrait;
  use ServicesTrait;

  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('class-name','',InputOption::VALUE_OPTIONAL, 'Form name'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
        new InputOption('inputs','',InputOption::VALUE_OPTIONAL, 'Create a inputs in a form'),
        new InputOption('routing', '', InputOption::VALUE_NONE, 'Update routing'),
      ))
      ->setDescription('Generate form')
      ->setHelp('The <info>generate:form</info> command helps you generate a new form.')
      ->setName('generate:form');
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

    // if exist form generate config file
    $inputs = $input->getOption('inputs');
    $build_services = $this->buildServices($services);

    $this
      ->getGenerator()
      ->generate($module, $class_name, $build_services, $inputs, $update_routing);

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
      $modules = $this->getModules();
      $module = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion('Enter your module'),
        function ($module) {
          return $this->validateModuleExist($module);
        },
        false,
        '',
        $modules
      );
    }
    $input->setOption('module', $module);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      ;$name = $dialog->ask($output, $dialog->getQuestion('Enter the form name', 'DefaultForm'), 'DefaultForm');
    }
    $input->setOption('class-name', $name);

    $services_collection = $this->servicesQuestion($input, $output, $dialog);
    $input->setOption('services', $services_collection);

    // --inputs option
    $inputs = $input->getOption('inputs');
    if (!$inputs) {
      // see more in \Drupal\AppConsole\Command\Helper\FormTrait
      $inputs = $this->formQuestion($input, $output, $dialog);  
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
