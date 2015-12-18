<?php

/**
 * @file
 * Contains Drupal\Console\Helper\KernelHelper.
 */

namespace Drupal\Console\Helper;

use Composer\Autoload\ClassLoader;
use Drupal\Console\Helper\Helper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DrupalKernel;

/**
 * Class KernelHelper
 * @package Drupal\Console\Helper
 */
class KernelHelper extends Helper
{
    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $classLoader;

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
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    protected $requestUri;

    /**
     * @var bool
     */
    protected $booted;

    /**
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param string $requestUri
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * @return bool
     */
    public function bootKernel()
    {
        if (!$this->booted) {
            $kernel = $this->getKernel();
            if ($this->getDrupalHelper()->isConnectionInfo()) {
                $kernel->boot();
                $kernel->preHandle($this->request);
                $container = $kernel->getContainer();
                $container->set('request', $this->request);
                $container->get('request_stack')->push($this->request);
                $this->booted = true;
            }
        }

        return $this->booted;
    }

    /**
     * @return \Drupal\Core\DrupalKernel
     */
    public function getKernel()
    {
        // Add support for Acquia Dev Desktop sites on Mac OS X
        $devdesktop_dir = getenv('HOME') . "/.acquia/DevDesktop/DrupalSettings";
        if (file_exists($devdesktop_dir)) {
            $_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] = $devdesktop_dir;
        }

        if (!$this->kernel) {
            if ($this->requestUri) {
                $this->request = Request::create($this->requestUri);
                $this->request->server->set('SCRIPT_NAME', '/index.php');
            } else {
                $this->request = Request::createFromGlobals();
            }

            $this->kernel = DrupalKernel::createFromRequest(
                $this->request,
                $this->classLoader,
                $this->environment
            );
        }

        return $this->kernel;
    }

    /**
     * @return void
     */
    public function terminate()
    {
        if ($this->booted) {
            $response = Response::create('');
            $this->kernel->terminate($this->request, $response);
        }

        return;
    }

    /**
     * @param \Drupal\Core\DrupalKernel $kernel
     */
    public function setKernel(DrupalKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->getKernel()->getContainer()->get('event_dispatcher');
    }

    /**
     * @return boolean
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public function getClassLoader()
    {
        return $this->classLoader;
    }

    /**
     * @param \Composer\Autoload\ClassLoader $classLoader
     */
    public function setClassLoader(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
   *
   */
    public function getSitePath()
    {
        return $this->getKernel()->getSitePath();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'kernel';
    }
}
