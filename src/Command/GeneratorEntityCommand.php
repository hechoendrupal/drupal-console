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
use Drupal\AppConsole\Generator\EntityGenerator;

class GeneratorEntityCommand extends GeneratorCommand
{
  use ModuleTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
        ->setDefinition(array(
            new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
            new InputOption('entity','',InputOption::VALUE_REQUIRED, 'The name of the entity'),
            new InputOption('class','',InputOption::VALUE_REQUIRED, 'The class of the entity')
        ))
        ->setName('generate:entity')
        ->setDescription('Generate entity')
        ->setHelp('The <info>generate:entity</info> command helps you generate a new entity.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $entity = $input->getOption('entity');
    $class = $input->getOption('class');
    
    $this
      ->getGenerator()
      ->generate($module, $entity, $class);

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

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --entity option
    $entity = $input->getOption('entity');
    if (!$entity) {
        $entity = $dialog->ask(
          $output,
          $dialog->getQuestion('Enter the entity name', '')
      );
    }
    $input->setOption('entity', $entity);

    // --entity option
    $entity = $input->getOption('entity');
    if (!$entity) {
    	$entity = $dialog->ask(
    			$output,
    			$dialog->getQuestion('Enter the entity name', '')
    	);
    }
    $input->setOption('entity', $entity);
    
    // --class option
    $class = $input->getOption('class');
    if (!$class) {
    	$class = $dialog->askConfirmation(
    			$output,
    			$dialog->getQuestion('Enter the class of the entity', 'ConfigEntityType', '?'),
    			true
    	);
    }
    $input->setOption('class', $class);    
  }


  protected function createGenerator()
  {
    return new EntityGenerator();
  }
}

