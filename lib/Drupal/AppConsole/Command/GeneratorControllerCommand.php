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
use Drupal\AppConsole\Generator\ModuleGenerator;

class GeneratorControllerCommand extends GeneratorCommand {

  protected function configure() {


    $this
      ->setDefinition(array(
        new InputOption('module','',InputOption::VALUE_REQUIRED, 'The name of the module'),
        new InputOption('name','',InputOption::VALUE_OPTIONAL, 'Controller name'),
        new InputOption('services','',InputOption::VALUE_OPTIONAL, 'Load services'),
        new InputOption('routing', '', InputOption::VALUE_NONE, 'Update routing'),
      ))
      ->setDescription('Generate controller')
      ->setHelp('The <info>generate:controller</info> command helps you generate a new controller.')
      ->setName('generate:controller');
  }

  /**
   * Execute method
   * @param  InputInterface  $input  [description]
   * @param  OutputInterface $output [description]
   * @return [type]                  [description]
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $mod = array();
    $modules = system_rebuild_module_data();
    foreach ($modules as $filename => $module) {
      if ( !preg_match('/core/',$module->uri) ){

        array_push($mod, array($filename=>$module->uri));
      }
    }

    print_r($mod);
  }

  /**
    * Get a filesystem
    * @return [type] Drupal Filesystem
    */
  protected function createGenerator() {
    return new ModuleGenerator();
  }

}
