<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\Helper\RegisterCommandsHelper
 */
namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;
use Drupal\AppConsole\Console\Application;

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
        $success = false;
        if ($this->console->isBooted()) {
            $commands = $this->getCommands();
        } else {
            $commands = $this->getConsoleCommands();
        }

        if (!$commands) {
            return false;
        }

        foreach ($commands as $command) {
            $this->console->add($command);
            $success = true;
        }

        return $success;
    }

    private function findCommands($modules, $namespaces)
    {
        $commands = [];
        $finder = new Finder();
        foreach ($modules as $module => $directory) {
            $place = $namespaces['Drupal\\' . $module];
            $cmd_dir = '/Command';
            $prefix = 'Drupal\\' . $module . '\\Command';

            if (is_dir($place . $cmd_dir)) {
                $dir = $place . $cmd_dir;
            } else {
                continue;
            }

            $finder->files()
              ->name('*Command.php')
              ->in($dir)
              ->depth('< 2');

            foreach ($finder as $file) {
                $ns = $prefix;

                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\' . strtr($relativePath, '/', '\\');
                }
                $class = $ns . '\\' . $file->getBasename('.php');

                if (class_exists($class)) {
                    $cmd = new \ReflectionClass($class);

                    if ($cmd->isAbstract()) {
                        continue;
                    }

                    if (!$cmd->isSubclassOf('Drupal\\AppConsole\\Command\\Command')) {
                        continue;
                    }

                    if (!$this->console->isBooted() && $cmd->isSubclassOf('Drupal\\AppConsole\\Command\\ContainerAwareCommand')) {
                        continue;
                    }

                    if ($cmd->getConstructor()->getNumberOfRequiredParameters() > 0) {
                        $translator = $this->getHelperSet()->get('translator');
                        if ($module && $module != 'AppConsole') {
                            $translator->addResourceTranslationsByModule($module);
                        }
                        $command = $cmd->newInstance($translator);
                    } else {
                        $command = $cmd->newInstance();
                    }
                    $command->setModule($module);
                    $commands[] = $command;
                }
            }
        }

        return $commands;
    }

    public function getCommands()
    {
        $consoleCommands = $this->getConsoleCommands();
        $customCommands = $this->getCustomCommands();

        return array_merge($consoleCommands, $customCommands);
    }

    public function getConsoleCommands()
    {
        $modules = ['AppConsole' => dirname(dirname(dirname(__DIR__)))];
        $namespaces = ['Drupal\\AppConsole' => dirname(dirname(__DIR__))];

        return $this->findCommands($modules, $namespaces);
    }

    public function getCustomCommands()
    {
        $modules = $this->getModuleList();
        $namespaces = $this->getNamespaces();

        return $this->findCommands($modules, $namespaces);
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
        // Get Module handler
        if (!isset($this->modules)) {
            $this->getContainer();
            $module_handler = $this->container->get('module_handler');
            $this->modules = $module_handler->getModuleDirectories();
        }

        return $this->modules;
    }

    protected function getNamespaces()
    {
        // Get Traversal, namespaces
        if (!isset($this->namespaces)) {
            $this->getContainer();
            $namespaces = $this->container->get('container.namespaces');
            $this->namespaces = $namespaces->getArrayCopy();
        }

        return $this->namespaces;
    }
}
