<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\DrupalKernel;

class KernelHelper extends Helper {

  /**
   * @var DrupalKernel
   */
  protected $kernel;

  /**
   * @var string
   */
  protected $environment;

  /**
   * @var boolean
   */
  protected $debug;

  /**
   * @param DrupalKernel $kernel
   */
  public function setKernel(DrupalKernel $kernel) {
    $this->kernel = $kernel;
  }

  /**
   * @return DrupalKernel
   */
  public function getKernel() {
    if (!$this->kernel) {
      $this->kernel = new DrupalKernel($this->environment, drupal_classloader(), !$this->debug);
    }

    return $this->kernel;
  }

  /**
   * @param string $environment
   */
  public function setEnvironment($environment) {
  	$this->environment = $environment;
  }

  /**
   * @param boolean $debug
   */
  public function setDebug($debug) {
  	$this->debug = $debug;
  }

  /**
   * @return void
   */
  public function bootKernel() {
  	$this->getKernel()->boot();
  }

  /**
   *
   * @param array $commands
   */
  public function initCommands(array $commands) {
    $container = $this->getKernel()->getContainer();

    array_walk($commands, function($command) use ($container) {
      if ($command instanceof ContainerAwareInterface) {
        $command->setContainer($container);
      }
    });
  }

  public function getEventDispatcher() {
    $this->getKernel()->getContainer()->get('event_dispatcher');
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName() {
      return 'kernel';
  }
}
