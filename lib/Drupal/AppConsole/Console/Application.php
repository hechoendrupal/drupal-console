<?php

namespace Drupal\AppConsole\Console;

use Drupal\Core\DrupalKernel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\AppConsole\Command;
use Drupal\AppConsole\Command\TestCommand;

class Application extends BaseApplication {

  protected $kernel;

  /**
   * Create a new application extended from \Symfony\Component\Console\Application
   * @param DrupalKernel $kernel
   */
  public function __construct(DrupalKernel $kernel) {
    $this->kernel = $kernel;
    $env = 'prod';

    parent::__construct('Drupal', 'Drupal Core - 8.x/ '. $env );

    $this->getDefinition()->addOption(new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.'));
    $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $env ) );
    $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));

  }

  /**
   * Return a Drupal Kernel
   * @return DrupalKernel return a Drupal Kernel
   */
  public function getKernel(){
    return $this->kernel;
  }

  /**
   * Run
   * @param  InputInterface  $input  [description]
   * @param  OutputInterface $output [description]
   * @return [type]                  [description]
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    $this->kernel->boot();
    $container = $this->kernel->getContainer();

    foreach ($this->all() as $command) {
      if ($command instanceof ContainerAwareInterface) {
        $command->setContainer($container);
      }
    }

    $this->setDispatcher($container->get('event_dispatcher'));

    if (true === $input->hasParameterOption(array('--shell', '-s'))) {
      $shell = new Shell($this);
      $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
      $shell->run();

      return 0;
    }

    return parent::doRun($input, $output);
  }

  /**
   * Register all commands
   * @return [type] [description]
   */
  public function getDefaultCommands() {
    $commands = parent::getDefaultCommands();
    $commands[] = new \Drupal\AppConsole\Command\GeneratorModuleCommand();
    $commands[] = new \Drupal\AppConsole\Command\GeneratorControllerCommand();
    $commands[] = new \Drupal\AppConsole\Command\ServicesCommand();
    return $commands;
  }

}

