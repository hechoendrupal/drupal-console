<?php
namespace Drupal\AppConsole\Command;

use Drupal\AppConsole\Command\GeneratorCommand;
use Drupal\AppConsole\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Generator\FormGenerator;
use Drupal\AppConsole\Command\Validators;

class GeneratorFormCommand extends GeneratorCommand {

  protected function configure() {

    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('name','',InputOption::VALUE_OPTIONAL, 'Form name'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
        new InputOption('inputs','',InputOption::VALUE_OPTIONAL, 'Create a inputs in a form'),
        new InputOption('config_file','',InputOption::VALUE_OPTIONAL,'Create a config file'),
        new InputOption('routing', '', InputOption::VALUE_NONE, 'Update routing'),
      ))
      ->setDescription('Generate form')
      ->setHelp('The <info>generate:form</info> command helps you generate a new form.')
      ->setName('generate:form');
  }

  /**
   * Execute method
   * @param  InputInterface  $input  [description]
   * @param  OutputInterface $output [description]
   * @return [type]                  [description]
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $dialog = $this->getDialogHelper();

    $module = $input->getOption('module');
    $services = $input->getOption('services');
    $update_routing = $input->getOption('routing');
    $class_name = $input->getOption('name');

    // if exist form generate config file
    $inputs = $input->getOption('inputs');
    if ($inputs){
      $generate_config = $input->getOption('config_file');
      // need config.factory services
      if ($generate_config){
        //check if config.factory not is in services array
        if (!in_array('config.factory',$services)){
          $services[] = 'config.factory';
        }
      }
    }
    else{
      $generate_config = false;
    }

    $map_service = array();

    foreach ($services as $service) {
      $class = get_class($this->getContainer()->get($service));
      $map_service[$service] = array(
        'name'  => $service,
        'machine_name' => str_replace('.', '_', $service),
        'class' => $class,
        'short' => end(explode('\\',$class))
      );
    }

    $generator = $this->getGenerator();
    $generator->generate($module, $class_name, $map_service, $inputs, $generate_config, $update_routing);

    $dialog->writeGeneratorSummary($output, $errors);

  }

  /**
   * [interact description]
   * @param  InputInterface  $input  [description]
   * @param  OutputInterface $output [description]
   * @return [type]                  [description]
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal form generator');

    $d = $this->getHelperSet()->get('dialog');

    // Module name
    $modules = $this->getModules();
    $module = $d->askAndValidate(
      $output,
      $dialog->getQuestion('Enter your module '),
      function($module) use ($modules){
        return Validators::validateModuleExist($module, $modules);
      },
      false,
      '',
      $modules
    );
    $input->setOption('module', $module);

    // Controller name
    $name = $this->getName();
    $name = $dialog->ask($output, $dialog->getQuestion('Enter the form name', 'DefaultForm'), 'DefaultForm');
    $input->setOption('name', $name);

    // Add services
    // TODO: Create a method for this job
    if ($dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Do you like add service(s)?', 'yes', '?'),
      true
    )) {
      $service_collection = array();
      $services = $this->getServices();
      while(true){
        $service = $d->askAndValidate(
          $output,
          $dialog->getQuestion('Enter your service (optional): '),
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

    // Form fields
    // TODO: Create a method for this job
    if ($dialog->askConfirmation(
      $output,
      $dialog->getQuestion('Do you like generate a form structure?', 'yes', '?'),
      true
    )) {
      $input_types = array(
        'text',
        'password',
        'submit',
        'color',
        'date',
        'datetime',
        'datetime-local',
        'email',
        'month',
        'number',
        'range',
        'search',
        'tel',
        'time',
        'url',
        'week');
      $inputs = array();
      while(true){

        // Label for input
        $input_label = $dialog->ask(
          $output,
          $dialog->getQuestion(' Input label','',':'),
          null
        );

        // break if is blank
        if ($input_label == null) {
          break;
        }

        // Machine name
        $input_machine_name = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $input_label);
        $input_machine_name = preg_replace('@[^a-z0-9_]+@','_',strtolower($input_machine_name));

        $input_name = $dialog->ask(
          $output,
          $dialog->getQuestion('  Input machine name', $input_machine_name, ':'),
          $input_machine_name
        );

        // Type input
        // TODO: validate
        $input_type = $d->askAndValidate(
          $output,
          $dialog->getQuestion('  Type'),
          function($input) use ($input_types){
            return $input;
          },
          false,
          null,
          $input_types
        );

        array_push($inputs, array(
          'name'  => $input_name,
          'type'  => $input_type,
          'label' => $input_label
        ));
      }
      $input->setOption('inputs', $inputs);

      // Generate config file
      if ($dialog->askConfirmation(
        $output,
        $dialog->getQuestion(' Do you like generate config file?', 'yes', '?'),
        true
      )) {
       $input->setOption('config_file',true);
      }

    }

    // Routing
    $routing = $input->getOption('routing');
    if (!$routing && $dialog->askConfirmation($output, $dialog->getQuestion('Update routing file?', 'yes', '?'), true)) {
        $routing = true;
    }
    $input->setOption('routing', $routing);

  }

  /**
    * Get a filesystem
    * @return [type] Drupal Filesystem
    */
  protected function createGenerator() {
    return new FormGenerator();
  }

}
