<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\KernelHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DrupalKernel;

class KernelHelper extends Helper
{
    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $class_loader;

    /**
     * @var \Drupal\Core\DrupalKernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var boolean
     */
    protected $debug;

    public function setClassLoader(ClassLoader $class_loader)
    {
        $this->class_loader = $class_loader;
    }

    /**
     * @param \Drupal\Core\DrupalKernel $kernel
     */
    public function setKernel(DrupalKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return \Drupal\Core\DrupalKernel
     */
    public function getKernel()
    {
        if (!$this->kernel) {
            $this->request = Request::createFromGlobals();
            $this->kernel = DrupalKernel::createFromRequest(
              $this->request,
              $this->class_loader,
              $this->environment
            );
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
        $kernel = $this->getKernel();
        $kernel->boot();
        $kernel->preHandle($this->request);

        $container = $kernel->getContainer();
        $container->set('request', $this->request);
        $container->get('request_stack')->push($this->request);
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'kernel';
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public function getClassLoader(){
        return $this->class_loader;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest(){
        return $this->request;
    }
}
