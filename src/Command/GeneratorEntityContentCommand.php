<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorEntityContentCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\EntityContentGenerator;

class GeneratorEntityContentCommand extends GeneratorCommand
{
  use ModuleTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
        ->setDefinition(array(
          new InputOption('module',null,InputOption::VALUE_REQUIRED, 'The name of the module'),
          new InputOption('entity-class',null,InputOption::VALUE_REQUIRED, 'The entity class name'),
          new InputOption('entity-name',null,InputOption::VALUE_REQUIRED, 'The name of the entity'),
        ))
        ->setName('generate:entity:content')
        ->setDescription('Generate entity configuration')
        ->setHelp('The <info>generate:entity:content</info> command helps you generate a new entity.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $entity_class = $input->getOption('entity-class');
    $entity_name = $input->getOption('entity-name');

    $this
      ->getGenerator()
      ->generate($module, $entity_name, $entity_class);

    $errors = [];
    $dialog->writeGeneratorSummary($output, $errors);
  }


  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal content entity generator');
    $utils = $this->getStringUtils();

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --entity-class option
    $entity_class = $input->getOption('entity-class');
    if (!$entity_class) {
      $entity_class = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the entity class name', 'DefaultEntity'),
        'DefaultEntity',
        null
      );
    }
    $input->setOption('entity-class', $entity_class);

    $machine_name = $utils->camelCaseToMachineName($entity_class);

    // --entity-name option
    $entity_name = $input->getOption('entity-name');
    if (!$entity_name) {
        $entity_name = $dialog->ask(
          $output,
          $dialog->getQuestion('Enter the entity name', $machine_name),
          $machine_name,
          null
      );
    }
    $input->setOption('entity-name', $entity_name);
  }


  protected function createGenerator()
  {
    return new EntityContentGenerator();
  }
}
