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

  protected $entity_type;

  /**
   * {@inheritdoc}
   */
  protected function configure($entity_type, $command_name)
  {
    $this->entity_type = $entity_type;
    $this
        ->setDefinition(array(
          new InputOption('module',null,InputOption::VALUE_REQUIRED, 'The name of the module'),
          new InputOption('entity-class',null,InputOption::VALUE_REQUIRED, 'The entity class name'),
          new InputOption('entity-name',null,InputOption::VALUE_REQUIRED, 'The name of the entity'),
        ))
        ->setName($command_name)
        ->setDescription('Generate '.$entity_type)
        ->setHelp('The <info>'.$command_name.'</info> command helps you generate a new '. $entity_type);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $entity_class = $input->getOption('entity-class');
    $entity_name = $input->getOption('entity-name');

    $entity_type = $this->getStringUtils()->camelCaseToUnderscore($this->entity_type);

    $this
      ->getGenerator()
      ->generate($module, $entity_name, $entity_class, $entity_type);

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
    switch ($entity_name) {
      case 'entity_content':
        $generator = new EntityContentGenerator;
        break;
      default:
        $generator = new EntityConfigGenerator;
        break;
    }

    return $generator;
  }
}
