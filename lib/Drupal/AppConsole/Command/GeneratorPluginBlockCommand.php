<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\GeneratorPluginBlockCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Generator\PluginBlockGenerator;

class GeneratorPluginBlockCommand extends GeneratorCommand{

  protected function configure() {
    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('name','',InputOption::VALUE_OPTIONAL, 'Block name'),
        new InputOption('description','',InputOption::VALUE_OPTIONAL, 'Description block'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
      ))
    ->setDescription('Generate plugin block')
    ->setHelp('The <info>generate:plugin:block</info> command helps you generate a new controller.')
    ->setName('generate:plugin:block');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $dialog = $this->getDialogHelper();

    if ($input->isInteractive()) {
      if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
        $output->writeln('<error>Command aborted</error>');
        return 1;
      }
    }

    $module = $input->getOption('module');
    $name = $input->getOption('name');
    $description = $input->getOption('description');
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
      ->generate($module, $name, $description, $map_service)
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output) {
    $dialog = $this->getDialogHelper();
    $dialog->writeSection($output, 'Welcome to the Drupal Plugin Block generator');

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
          return $this->validateModuleExist($module);
        },
        false,
        '',
        $modules
      );
    }

    $input->setOption('module', $module);

    // --name option
    $name = $input->getOption('name');
    if (!$name){
      $name = $dialog->ask($output, $dialog->getQuestion('Enter the controller name', 'DefaultBlock'), 'DefaultBlock');
      $input->setOption('name', $name);
    }
    $input->setOption('name', $name);

    $description = $input->getOption('description');
    if (!$description) {
      $description = $dialog->ask($output, $dialog->getQuestion('Description', 'My Awesome Block'), 'My Awesome Block');
    }
    $input->setOption('description', $description);

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
            return $this->validateServiceExist($service, $services);
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
    return new PluginBlockGenerator();
  }

}
