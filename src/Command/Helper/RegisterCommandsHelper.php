<?php
/**
 *@file
 * Contains \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper
 */
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Application;

class RegisterCommandsHelper extends Helper
{

  /**
   * @var \Symfony\Component\Console\Application.
   */
  protected $console;

  protected $container;
  protected $kernel;
  protected $modules;
  protected $namespaces;

  public function __construct(Application $console)
  {
    $this->console = $console;
  }

  public function register()
  {

    $this->getModuleList();
    $this->getNamespaces();

    $finder = new Finder();
    foreach ($this->modules as $module => $directory) {
      $place   = $this->namespaces['Drupal\\'.$module];
      $cmd_dir = '/Command';
      $prefix  = 'Drupal\\'.$module.'\\Command';

      if (is_dir($place.$cmd_dir)) {
        $dir = $place.$cmd_dir;
      }
      else {
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

        if (class_exists($class)) {
          $cmd = new \ReflectionClass($class);
          // if is a valid command
          if ($cmd->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')
            && !$cmd->isAbstract()
            && !$cmd->getConstructor()->getNumberOfRequiredParameters()) {

            // Register command
            $this->console->add($cmd->newInstance());
          }
        }
      }
    }
  }

  /**
   * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
   */
  public function getName()
  {
    return 'register_commands';
  }

  protected function getKernel()
  {
    if (!isset($this->kernel)) {
      $kernelHelper = $this->getHelperSet()->get('kernel');
      $this->kernel = $kernelHelper->getKernel();
    }
  }

  protected function getContainer()
  {
    $this->getKernel();
    if (!isset($this->container)) {
      $this->container = $this->kernel->getContainer();
    }
  }

  protected function getModuleList()
  {
    // Get Container
    $this->getContainer();
    // Get Module handler
    if (!isset($this->modules)) {
      $module_handler = $this->container->get('module_handler');
      $this->modules = $module_handler->getModuleDirectories();
      $this->modules += ['AppConsole' => dirname(dirname(dirname(__DIR__)))];
    }
  }

  protected function getNamespaces()
  {
    $this->getContainer();
    // Get Traversal, namespaces
    if (!isset($this->namespaces)) {
      $namespaces = $this->container->get('container.namespaces');
      $this->namespaces = $namespaces->getArrayCopy();
      $this->namespaces += ['Drupal\\AppConsole' => dirname(dirname(__DIR__))];
    }
  }
}
