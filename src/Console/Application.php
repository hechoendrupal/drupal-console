<?php
namespace Drupal\AppConsole\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Console\Input\ArrayInput;

class Application extends BaseApplication
{
  private $commandsRegistered = false;

  private $autoload = false;

  protected $booted = false;

  protected $config;

  protected $directoryRoot;

  protected $errorMessages = [];

  protected $drupalAutoload;

  /**
   * Create a new application extended from \Symfony\Component\Console\Application
   * @param $config array
   */
  public function __construct($config)
  {
    $this->config = $config;

    $name = $config['application']['name'];
    $environment = $config['application']['environment'];
    $version = $config['application']['version'];

    parent::__construct(
      $name,
      sprintf('%s', $version)
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
    $this->autoload();

    if ($this->isBooted()) {

      if ($this->autoload) {
        $this->initDebug($input);
        $this->doKernelConfiguration();
      }

      if (!$this->commandsRegistered) {
        $this->commandsRegistered = $this->registerCommands();
      }

      if (true === $input->hasParameterOption(array('--shell', '-s'))) {
        $this->runShell($input);

        return 0;
      }
    }

    $name = $this->getCommandName($input);

    if ($name != '' && !$this->find ($name)) {
      if (!$this->errorMessages) {
        $translator = $this->getHelperSet()->get('translator');
        $this->errorMessages[] = sprintf(
          $translator->trans('application.console.errors.invalid-command'),
          $name
        );
      }
      //$name = $this->defaultCommand;
      //$input = new ArrayInput(array('command' => $name));
    }

    parent::doRun($input, $output);

    foreach ($this->errorMessages as $errorMessage) {
      $this->renderException(new \Exception($errorMessage), $output);
    }
  }

  protected function autoload()
  {
    $this->drupalAutoload = $this
      ->getHelperSet()
      ->get('drupal-autoload')
      ->findAutoload();

    if ($this->drupalAutoload && !$this->isBooted()) {
      require $this->drupalAutoload;
      $this->setBooted(true);
    }
    else {
      return null;
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

  /**
   * @return \Drupal\Core\DrupalKernel | null
   */
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

  /**
   * @param array $helpers
   */
  public function addHelpers(array $helpers){
    $defaultHelperset = $this->getHelperSet();
    foreach ($helpers as $alias => $helper) {
      $defaultHelperset->set($helper, is_int($alias) ? null : $alias);
    }
  }

  /**
   * @param array $errorMessages
   */
  public function addErrorMessages(array $errorMessages){
    $this->errorMessages = $errorMessages;
  }
}
