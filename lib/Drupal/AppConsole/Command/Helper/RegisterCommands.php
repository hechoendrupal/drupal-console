<?php
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Application;

class RegisterCommands extends Helper {

	protected $console;

	public function __construct(Application $console){
		$this->console = $console;
	}


	public function register(){
		// Get Container
    $kernelHelper = $this->getHelperSet()->get('kernel');
    $kernel = $kernelHelper->getKernel();
    $container = $kernel->getContainer();

    // Get Module handler
    $module_handler = $container->get('module_handler');
    $modules = $module_handler->getModuleDirectories();

    // Get Transversal, namespaces
    $namespaces = $container->get('container.namespaces');
    $namespaces = $namespaces->getArrayCopy();

    $finder = new Finder();
    foreach ($modules as $module => $directory) {

      $place = $namespaces['Drupal\\'.$module];
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

}