<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\KernelHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DrupalKernel;

class KernelHelper extends Helper
{
  private $class_loader;
  
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
  public function setKernel(DrupalKernel $kernel)
  {
    $this->kernel = $kernel;
  }

  /**
   * @return DrupalKernel
   */
  public function getKernel()
  {
    if (!$this->kernel) {
      $this->kernel = new DrupalKernel($this->environment, \drupal_classloader(), !$this->debug);
    }

    return $this->kernel;
  }

  /**
   * @param string $environment
   */
  public function setEnvironment($environment)
  {
    $this->environment = $environment;
  }

  /**
   * @param boolean $debug
   */
  public function setDebug($debug)
  {
    $this->debug = $debug;
  }

  /**
   * @return void
   */
  public function bootKernel()
  {
    $request = Request::createFromGlobals();
    $site_path = DrupalKernel::findSitePath($request, FALSE);

    $this->getKernel();
    $this->kernel->setSitePath($site_path);
    $this->kernel->boot();

    $container = $this->getKernel()->getContainer();
    $container->set('request', $request);
    $container->get('request_stack')->push($request);

    // Load Drupal Bootstrap Code: load code for subsystems and modules.
    $this->getHelperSet()->get('bootstrap')->bootstrapCode();

    // Register Validator Service manually
    $container->set(
      'console.validators',
      new \Drupal\AppConsole\Utils\Validators()
    );

    // Register StringUtils Service manually
    $container->set(
      'console.string_utils',
      new \Drupal\AppConsole\Utils\StringUtils()
    );
  }

  /**
   * @param array $commands
   */
  public function initCommands(array $commands)
  {
    $container = $this->getKernel()->getContainer();
    array_walk($commands, function ($command) use ($container) {
      if ($command instanceof ContainerAwareInterface) {
        $command->setContainer($container);
      }
    });
  }

  /**
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public function getEventDispatcher()
  {
    return $this->getKernel()->getContainer()->get('event_dispatcher');
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName()
  {
      return 'kernel';
  }
}
