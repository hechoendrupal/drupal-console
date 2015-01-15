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
          new InputOption('module',null,InputOption::VALUE_REQUIRED, $this->trans('common.options.module')),
          new InputOption('entity-class',null,InputOption::VALUE_REQUIRED, $this->trans('command.generate.entity.options.entity-class')),
          new InputOption('entity-name',null,InputOption::VALUE_REQUIRED, $this->trans('command.generate.entity.options.entity-name')),
        ))
        ->setName($this->commandName)
        ->setDescription(
          sprintf(
            $this->trans('command.generate.entity.description'),
            $this->entityType
          )
        )
        ->setHelp(
          sprintf(
            $this->trans('command.generate.entity.help'),
            $this->commandName,
            $this->entityType
          )
        )
    ;
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
        $dialog->getQuestion($this->trans('command.generate.entity.questions.entity-class'), $entity_class),
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
        $dialog->getQuestion($this->trans('command.generate.entity.questions.entity-name'), $machine_name),
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
