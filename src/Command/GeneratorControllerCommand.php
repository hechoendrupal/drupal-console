<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorControllerCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\ControllerGenerator;

class GeneratorControllerCommand extends GeneratorCommand
{
  use ModuleTrait;
  use ServicesTrait;
  use ConfirmationTrait;

  protected function configure()
  {
    $this
      ->setName('generate:controller')
      ->setDescription($this->trans('commands.generate.controller.description'))
      ->setHelp($this->trans('commands.generate.controller.command.help'))
      ->addOption('module','',InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
      ->addOption('class-name','',InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.controller.options.class-name'))
      ->addOption('method-name','',InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.controller.options.method-name'))
      ->addOption('route','',InputOption::VALUE_OPTIONAL, $this->trans('commands.generate.controller.options.route'))
      ->addOption('services','',InputOption::VALUE_OPTIONAL, $this->trans('commands.common.options.services'))
      ->addOption('test', '', InputOption::VALUE_NONE, $this->trans('commands.generate.controller.options.test'))
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    if ($this->confirmationQuestion($input, $output, $dialog)) {
      return;
    }

    $module = $input->getOption('module');
    $class_name = $input->getOption('class-name');
    $method_name = $input->getOption('method-name');
    $route = $input->getOption('route');
    $test = $input->getOption('test');
    $services = $input->getOption('services');

    // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
    $build_services = $this->buildServices($services);

    //Controller machine name
    $class_machine_name = $this->getStringUtils()->camelCaseToMachineName($class_name);

    $this->getGenerator()
      ->generate($module, $class_name, $method_name, $route, $test, $build_services, $class_machine_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($output, $dialog);
    }
    $input->setOption('module', $module);

    // --class-name option
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = 'DefaultController';
      $class_name = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('commands.generate.controller.questions.class-name'), $class_name),
        function ($class_name) {
          return $this->validateClassName($class_name);
        },
        false,
        $class_name,
        null
      );
    }
    $input->setOption('class-name', $class_name);

    // --method-name option & --route option
    if($class_name != 'DefaultController'){
      $method_name = $input->getOption('method-name');
      if (!$method_name) {
        $method_name = $dialog->ask(
          $output,
          $dialog->getQuestion($this->trans('commands.generate.controller.questions.method-name'), 'index'),
          'index'
        );
      }

      $route = $input->getOption('route');
      if (!$route) {
        $route = $dialog->ask(
          $output,
          $dialog->getQuestion($this->trans('commands.generate.controller.questions.route'), $module.'/'.$method_name),
          $module.'/'.$method_name
        );
      }
    }
    else{
      $method_name = 'hello';
      $route = $module.'/hello/{name}';
    }
    $input->setOption('method-name', $method_name);
    $input->setOption('route', $route);

    // --test option
    $test = $input->getOption('test');
    if (!$test && $dialog->askConfirmation(
      $output,
      $dialog->getQuestion($this->trans('commands.generate.controller.questions.test'), 'yes', '?'),
      TRUE
    )) {
      $test = true;
    }
    $input->setOption('test', $test);

    // --services option
    // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::servicesQuestion
    $services_collection = $this->servicesQuestion($output, $dialog);
    $input->setOption('services', $services_collection);
  }

  /**
   * @return \Drupal\AppConsole\Generator\ControllerGenerator
   */
  protected function createGenerator()
  {
    return new ControllerGenerator();
  }
}
