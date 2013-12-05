<?php
namespace Drupal\AppConsole\Console;

use Drupal\Core\DrupalKernel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class Application extends BaseApplication {

  /**
   * @var DrupalKernel
   */
  protected $kernel;

  /**
   * Create a new application extended from \Symfony\Component\Console\Application
   *
   * @param DrupalKernel $kernel
   */
  public function __construct(DrupalKernel $kernel) {
    $this->kernel = $kernel;
    $env = 'prod';

    parent::__construct('Drupal', 'Drupal App Console - 8.x/ ' . $env);

    $this->getDefinition()->addOption(
        new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.')
    );
    $this->getDefinition()->addOption(
        new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $env)
    );
    $this->getDefinition()->addOption(
        new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.')
    );
  }

  /**
   * Run
   * @param  InputInterface  $input
   * @param  OutputInterface $output
   * @return int
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    $this->kernel->boot();

    $container = $this->kernel->getContainer();
    $request = Request::createFromGlobals();
    $container->set('request', $request);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    foreach ($this->all() as $command) {
      if ($command instanceof ContainerAwareInterface) {
        $command->setContainer($container);
      }
    }

    $this->setDispatcher($container->get('event_dispatcher'));

    if (true === $input->hasParameterOption(array('--shell', '-s'))) {

      $shell = $this->getHelperSet()->get('shell')->getShell();
      $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
      $shell->run();

      return 0;
    }

    return parent::doRun($input, $output);
  }
}
