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

  private $autoload = false;

  protected $booted = false;

  protected $config;

  protected $directoryRoot;

  /**
   * Create a new application extended from \Symfony\Component\Console\Application
   */
  public function __construct($config)
  {
    $this->config = $config;

    $environment = $config['application']['environment'];
    $version = $config['application']['version'];

    parent::__construct(
      'Drupal Console',
      sprintf('%s (%s)', $version, $environment)
    );

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
      new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, 'The Environment name.', $environment)
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
    $this->autoload = $this->autoload();

    if ($this->autoload) {
      $this->initDebug($input);
      $this->doKernelConfiguration();
    }

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

  protected function autoload()
  {
    $autoload = $this
      ->getHelperSet()
        ->get('finder')
          ->findBootstrapFile();

    if ($autoload) {
      $autoload = require($autoload);
      return $autoload;
    }
    else {
      return false;
    }
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
    $kernelHelper->setClassLoader($this->autoload());
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
    $drupalModules = $this->autoload;
    $rc->register($drupalModules);
  }

  public function getKernel()
  {
    return $this->autoload ? $this->getHelperSet()->get('kernel')->getKernel() : null;
  }

  /**
   * @return boolean
   */
  public function isBooted()
  {
    return $this->booted;
  }

  /**
   * @param boolean $booted
   */
  public function setBooted($booted)
  {
    $this->booted = $booted;
  }

  /**
   * @return mixed
   */
  public function getConfig()
  {
    return $this->config;
  }

  /**
   * @param mixed $config
   */
  public function setConfig($config)
  {
    $this->config = $config;
  }

  /**
   * @return mixed
   */
  public function getDirectoryRoot()
  {
    return $this->directoryRoot;
  }

  /**
   * @param mixed $directoryRoot
   */
  public function setDirectoryRoot($directoryRoot)
  {
    $this->directoryRoot = $directoryRoot;
  }
}
