<?php
namespace Drupal\AppConsole\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;

class Application extends BaseApplication
{
  private $commandsRegistered = false;

  /**
   * Create a new application extended from \Symfony\Component\Console\Application
   */
  public function __construct()
  {
    $env = 'prod';

    parent::__construct('Drupal', 'Drupal App Console - 8.x/ ' . $env);

    $this->getDefinition()->addOption(
      new InputOption(
        '--bootstrap-file',
        '-b',
        InputOption::VALUE_OPTIONAL,
        'Path to Drupal bootstrap file (core/includes/boostrap.inc).'
      )
    );
    $this->getDefinition()->addOption(
      new InputOption('--shell', '-s', InputOption::VALUE_NONE, 'Launch the shell.')
    );
    $this->getDefinition()->addOption(
      new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, 'The Environment name.', $env)
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
  public function doRun(InputInterface $input, OutputInterface $output)
  {
    $this->bootstrapDrupal($input, $output);
    $this->initDebug($input);
    $this->doKernelConfiguration();

    if (!$this->commandsRegistered) {
      $this->registerCommands();
      $this->commandsRegistered = true;
    }

    if (true === $input->hasParameterOption(array('--shell', '-s'))) {
      $this->runShell($input);

      return 0;
    }

    return parent::doRun($input, $output);
  }

  protected function bootstrapDrupal(InputInterface $input, OutputInterface $output)
  {
    $drupalBoostrap = $this->getHelperSet()->get('bootstrap');

    $bootstrapFile = $input->getParameterOption(array('--bootstrap-file', '-b'));
    if (!$bootstrapFile) {
        $bootstrapFile = $this->getHelperSet()->get('finder')->findBootstrapFile($output);
    }

    $drupalBoostrap->bootstrapConfiguration($bootstrapFile);
  }

  protected function initDebug(InputInterface $input)
  {
    $env = $input->getParameterOption(array('--env', '-e'), getenv('DRUPAL_ENV') ?: 'prod');

    $debug = getenv('DRUPAL_DEBUG') !== '0'
        && !$input->hasParameterOption(array('--no-debug', ''))
        && $env !== 'prod';

    if ($debug) {
      Debug::enable();
    }

    $kernelHelper = $this->getHelperSet()->get('kernel');
    $kernelHelper->setDebug($debug);
    $kernelHelper->setEnvironment($env);
  }

  protected function doKernelConfiguration()
  {
    $kernelHelper = $this->getHelperSet()->get('kernel');
    $kernelHelper->bootKernel();
    $kernelHelper->initCommands($this->all());

    $this->setDispatcher($kernelHelper->getEventDispatcher());
  }

  protected function runShell(InputInterface $input)
  {
    $shell = $this->getHelperSet()->get('shell')->getShell();
    $shell->setProcessIsolation($input->hasParameterOption(array('--process-isolation')));
    $shell->run();
  }

  protected function registerCommands()
  {
    $rc = $this->getHelperSet()->get('register_commands');
    $rc->register();
  }
}
