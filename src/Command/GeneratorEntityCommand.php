<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorEntityCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\EntityConfigGenerator;
use Drupal\AppConsole\Generator\EntityContentGenerator;

abstract class GeneratorEntityCommand extends GeneratorCommand
{
  use ModuleTrait;
  private $entityType;
  private $commandName;

  /**
   * @param $entityType
   */
  protected function setEntityType($entityType)
  {
    $this->entityType = $entityType;
  }

  /**
   * @param $commandName
   */
  protected function setCommandName($commandName)
  {
    $this->commandName = $commandName;
  }

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
        ->setName($this->commandName)
        ->setDescription('Generate '.$this->entityType)
        ->setHelp('The <info>'.$this->commandName.'</info> command helps you generate a new '. $this->entityType);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $entityType = $this->getStringUtils()->camelCaseToUnderscore($this->entityType);
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $entity_class = $input->getOption('entity-class');
    $entity_name = $input->getOption('entity-name');

    $this
      ->getGenerator()
      ->generate($module, $entity_name, $entity_class, $entityType);

    $errors = [];
    $dialog->writeGeneratorSummary($output, $errors);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal entity generator');
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
      $entity_class = 'DefaultEntity';
      $entity_class = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion('Enter the entity class name', $entity_class),
        function($entity_class){
          return $this->validateSpaces($entity_class);
        },
        false,
        $entity_class,
        null
      );
    }
    $input->setOption('entity-class', $entity_class);

    $machine_name = $utils->camelCaseToMachineName($entity_class);

    // --entity-name option
    $entity_name = $input->getOption('entity-name');
    if (!$entity_name) {
      $entity_name = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion('Enter the entity name', $machine_name),
        function ($machine_name) {
          return $this->validateMachineName($machine_name);
        },
        false,
        $machine_name,
        null
      );
    }

    $input->setOption('entity-name', $entity_name);
  }

  protected function createGenerator()
  {
    if ('EntityContent' == $this->entityType)
      return new EntityContentGenerator();

    return new EntityConfigGenerator();
  }
}
