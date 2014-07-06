<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorServiceCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\ServiceGenerator;

class GeneratorServiceCommand extends GeneratorCommand
{
  use ServicesTrait;
  use ModuleTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition(array(
        new InputOption('module',null,InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('service-name',null,InputOption::VALUE_OPTIONAL, 'Service name'),
        new InputOption('class-name',null,InputOption::VALUE_OPTIONAL, 'Class name'),
        new InputOption('services',null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Load services'),
      ))
      ->setDescription('Generate service')
      ->setHelp('The <info>generate:service</info> command helps you generate a new service.')
      ->setName('generate:service');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    if ($input->isInteractive()) {
      if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
        $output->writeln('<error>Command aborted</error>');
        return 1;
      }
    }

    $module = $input->getOption('module');
    $service_name = $input->getOption('service-name');
    $class_name = $input->getOption('class-name');
    $services = $input->getOption('services');

    // @see Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
    $build_services = $this->buildServices($services);

    $this
      ->getGenerator()
      ->generate($module, $service_name, $class_name, $build_services);

    $errors = [];
    $dialog->writeGeneratorSummary($output, $errors);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal service generator');

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --service-name option
    $service_name = $input->getOption('service-name');
    if (!$service_name) {
      $service_name = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the service name', $module.'.default'),
        $module.'.default'
      );
    }
    $input->setOption('service-name', $service_name);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = $dialog->ask(
        $output,
        $dialog->getQuestion('Enter the Class name', 'DefaultService'),
        'DefaultService'
      );
    }
    $input->setOption('class-name', $class_name);

    // --services option
    $services = $input->getOption('services');
    if (!$services) {
      // @see Drupal\AppConsole\Command\Helper\ServicesTrait::servicesQuestion
      $services = $this->servicesQuestion($output, $dialog);
    }
    $input->setOption('services', $services);
  }

  protected function createGenerator()
  {
    return new ServiceGenerator();
  }
}
