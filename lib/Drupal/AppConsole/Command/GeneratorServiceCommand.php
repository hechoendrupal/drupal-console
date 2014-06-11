<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\GeneratorServiceCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\ServiceGenerator;
use Drupal\AppConsole\Command\Validators;

class GeneratorServiceCommand extends GeneratorCommand
{

  protected function configure() {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('service_name','',InputOption::VALUE_OPTIONAL, 'Service name'),
        new InputOption('class_name','',InputOption::VALUE_OPTIONAL, 'Class name'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
      ))
      ->setDescription('Generate service')
      ->setHelp('The <info>generate:service</info> command helps you generate a new service.')
      ->setName('generate:service');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $dialog = $this->getDialogHelper();

    if ($input->isInteractive()) {
      if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
        $output->writeln('<error>Command aborted</error>');
        return 1;
      }
    }

    $module = $input->getOption('module');
    $service_name = $input->getOption('service_name');
    $class_name = $input->getOption('class_name');
    $services = $input->getOption('services');

    $map_service = [];
    if (!empty($services)){
      foreach ($services as $service) {
        $class = get_class($this->getContainer()->get($service));
        $map_service[$service] = array(
          'name'  => $service,
          'machine_name' => str_replace('.', '_', $service),
          'class' => $class,
          'short' => end(explode('\\',$class))
        );
      }
    }

    $this
      ->getGenerator()
      ->generate($module, $service_name, $class_name, $map_service)
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output) {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal service generator');

    $helper_set = $this->getHelperSet()->get('dialog');

    // --module option
    $module = $input->getOption('module');
    if (!$module){
      // Module names
      $modules = $this->getModules();
      $module = $helper_set->askAndValidate(
        $output,
        $dialog->getQuestion('Enter your module',''),
        function($module) use ($modules){
          return Validators::validateModuleExist($module, $modules);
        },
        false,
        '',
        $modules
      );
    }

    $input->setOption('module', $module);

    // --service_name option
    $service_name = $input->getOption('service_name');
    if (!$service_name){
      $service_name = $dialog->ask($output, $dialog->getQuestion('Enter the service name', $module.'.default'), $module.'.default');
    }
    $input->setOption('service_name', $service_name);

    // --class option
    $class_name = $input->getOption('class_name');
    if (!$class_name){
      $class_name = $dialog->ask($output, $dialog->getQuestion('Enter the Class name', 'DefaultService'), 'DefaultService');
      $input->setOption('class_name', $class_name);
    }
    $input->setOption('class_name', $class_name);

    // --services option
    if ($dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Do you like add service(s)', 'yes', '?'),
      true
    )) {
      $service_collection = array();
      $services = $this->getServices();
      $output->writeln([
        '',
        'You can add some services, type the name or use keyup and keydown',
        'This is optional, press <info>enter</info> to <info>continue</info>',
        ''
      ]);

      while(true){
        $service = $helper_set->askAndValidate(
          $output,
          $dialog->getQuestion(' Enter your service',''),
          function($service) use ($services){
            return Validators::validateServiceExist($service, $services);
          },
          false,
          null,
          $services
        );

        if ($service == null) {
          break;
        }
        array_push($service_collection, $service);
        $service_key = array_search($service, $services, true);
        if ($service_key >= 0)
          unset($services[$service_key]);
      }
      $input->setOption('services', $service_collection);
    }

  }

  protected function createGenerator() {
    return new ServiceGenerator();
  }
}