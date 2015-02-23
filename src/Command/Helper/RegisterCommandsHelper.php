<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper
 */
namespace Drupal\AppConsole\Command\Helper;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;
use Drupal\AppConsole\Console\Application;
use Composer\Autoload\ClassLoader;

class RegisterCommandsHelper extends Helper
{

    /**
     * @var \Symfony\Component\Console\Application.
     */
    protected $console;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Drupal\Core\DrupalKernel
     */
    protected $kernel = null;

    /**
     * @var array
     */
    protected $modules;

    /**
     * @var array
     */
    protected $namespaces;

    /**
     * @param Application $console
     * @param AnnotationReader $reader
     * @param ClassLoader $autoload
     */
    public function __construct(Application $console, AnnotationReader $reader, ClassLoader $autoload)
    {
        $this->console = $console;
        $this->reader = $reader;
        $this->autoload = $autoload;
        $this->finder = new Finder();
    }

    /**
     * @return bool
     */
    public function register()
    {
        $this->registerAnnotation();
        $modules = $this->getModuleList();

        foreach ($modules as $module => $directory) {
            $command_dir = $directory . '/src/Command';

            if ($this->existCommandDirectory($command_dir)) {
                $commands = $this->searchCommands($command_dir);

                /** @var \Symfony\Component\Finder\SplFileInfo $command */
                foreach ($commands as $command) {
                    $class = $this->getFullyQualifiedPathName($module, $command);

                    if ($cmd = $this->isCommand($class)) {
                        $meta = $this->getCommandMetada($cmd);

                        // If the command need Drupal.
                        if ($meta && $this->console->isBooted() && $this->hasModule($meta->dependencies)) {
                            $command = $this->buildCommand($cmd, $module);
                            $this->console->add($command);
                        } else if (!$meta) { // If the command not need drupal.

                        }
                    }
                }

            }
        }

    }

    protected function getFullyQualifiedPathName($module, $command)
    {
        $prefix = 'Drupal\\' . $module . '\\Command';
        if ($relativePath = $command->getRelativePath()) {
            $prefix .= '\\' . strtr($relativePath, '/', '\\');
        }
        return $prefix . '\\' . $command->getBasename('.php');
    }

    protected function searchCommands($dir)
    {
        return $this->finder
            ->name('*Command.php')
            ->in($dir)
            ->depth('< 2')
        ;
    }

    protected function existCommandDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * @param $class
     * @return null|\ReflectionClass
     */
    protected function isCommand($class)
    {
        $class_command = 'Symfony\\Component\\Console\\Command\\Command';
        if (class_exists($class)) {
            $cmd = new \ReflectionClass($class);
            if ($cmd->isSubclassOf($class_command) && !$cmd->isAbstract()) {
                return $cmd;
            }
        }

        return null;
    }

    /**
     * @param \ReflectionClass $cmd
     * @param string $module
     * @return \Drupal\AppConsole\Command\Command
     */
    public function buildCommand(\ReflectionClass$cmd, $module)
    {
        if ($cmd->getConstructor()->getNumberOfRequiredParameters() > 0) {
            $translator = $this->getHelperSet()->get('translator');

            if ($module && $module != 'AppConsole') {
                $translator->addResourceTranslationsByModule($module);
            }

            $command = $cmd->newInstance($translator);
        } else {
            $command = $cmd->newInstance();
        }

        return $command;
    }

    /**
     * @param \ReflectionClass $cmd
     * @return \Drupal\AppConsole\Annotation\DrupalCommand
     */
    protected function getCommandMetada(\ReflectionClass $cmd)
    {
        return $this->reader->getClassAnnotation(
            $cmd,
            '\Drupal\AppConsole\Annotation\DrupalCommand'
        );
    }

    /**
     * @{@inheritdoc}
     */
    public function getName()
    {
        return 'register_commands';
    }

    protected function getKernel()
    {
        if (!$this->kernel) {
            /** @var \Drupal\AppConsole\Command\Helper\KernelHelper $kernelHelper */
            $kernelHelper = $this->getHelperSet()->get('kernel');
            $this->kernel = $kernelHelper->getKernel();
        }

        return $this->kernel;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        $this->getKernel();
        if (!isset($this->container)) {
            $this->container = $this->kernel->getContainer();
        }

        return $this->container;
    }

    /**
     * @param bool $drupalModules
     * @return array
     */
    protected function getModuleList()
    {
        // Get Module handler
        if (!isset($this->modules) ) {
            $this->modules = [];
            if ($this->console->isBooted()) {
                $this->container = $this->getContainer();
                /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
                $module_handler = $this->container->get('module_handler');
                $this->modules = $module_handler->getModuleDirectories();
            }
            $this->modules += ['AppConsole' => dirname(dirname(dirname(__DIR__)))];
        }

        return $this->modules;
    }

    protected function hasModule(array $dependencies)
    {
        return 0 == count(array_diff(
            array_values($dependencies),
            array_keys($this->modules)
        ));
    }

    /**
     * Register the command annotation.
     */
    protected function registerAnnotation()
    {
        AnnotationRegistry::registerLoader([
            $this->autoload,
            'loadClass'
        ]);
    }
}
