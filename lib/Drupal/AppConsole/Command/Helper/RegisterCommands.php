<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Application;

class RegisterCommands extends Helper {

  protected $console;
  protected $container;
  protected $kernel;
  protected $modules;
  protected $namespaces;

  public function __construct(Application $console) {
    $this->console = $console;
  }

  protected function getKernel() {
    if (!isset($this->kernel)){
      $kernelHelper = $this->getHelperSet()->get('kernel');
      $this->kernel = $kernelHelper->getKernel();
    }
  }

  public function register() {

    $this->getModuleList();
    $this->getNamespaces();

    $finder = new Finder();
    foreach ($this->modules as $module => $directory) {

      $place = $this->namespaces['Drupal\\'.$module];
      $dir = $place. '/Drupal/' . $module . '/Command';
      $prefix = 'Drupal\\'.$module . '\\Command';

      if (!is_dir($dir)) {
        continue;
      }

      $finder->files()
        ->name('*Command.php')
        ->in($dir)
        ->depth('< 2')
      ;

      foreach ($finder as $file) {
        $ns = $prefix;

        if ($relativePath = $file->getRelativePath()) {
          $ns .= '\\'.strtr($relativePath, '/', '\\');
        }

        $class = $ns.'\\'.$file->getBasename('.php');

        if (class_exists($class)){
          $r = new \ReflectionClass($class);
          // if is a valid command
          if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')
            && !$r->isAbstract()
            && !$r->getConstructor()->getNumberOfRequiredParameters()) {

            // Register command
            $this->console->add($r->newInstance());
          }
        }
      }
    }
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName() {
    return 'register_commands';
  }

  protected function getContainer() {
    $this->getKernel();
    if(!isset($this->container)){
      $this->container = $this->kernel->getContainer();
    }
  }

  protected function getModuleList() {
    // Get Container
    $this->getContainer();
    // Get Module handler
    if (!isset($this->modules)){
      $module_handler = $this->container->get('module_handler');
      $this->modules = $module_handler->getModuleDirectories();
    }
  }

  protected function getNamespaces() {
    $this->getContainer();
    // Get Transversal, namespaces
    if (!isset($this->namespaces)){
      $namespaces = $this->container->get('container.namespaces');
      $this->namespaces = $namespaces->getArrayCopy();
    }
  }

}