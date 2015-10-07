<?php
/**
 * @file
 * Contains \Drupal\Console\Helper\CommandDiscoveryHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class CommandDiscovery
 * @package Drupal\Console\Utils
 */
class CommandDiscoveryHelper extends Helper
{
    /**
     * @var string
     */
    protected $applicationRoot = '';

    /**
     * @var array
     */
    protected $disabledModules = [];

    /**
     * @param string $applicationRoot
     */
    public function setApplicationRoot($applicationRoot)
    {
        $this->applicationRoot = $applicationRoot;
    }

    /**
     * @param array $disabledModules
     */
    public function setDisabledModules($disabledModules)
    {
        $this->disabledModules = $disabledModules;
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        $consoleCommands = $this->getConsoleCommands();
        $customCommands = $this->getCustomCommands();

        return array_merge($consoleCommands, $customCommands);
    }

    /**
     * @return array
     */
    public function getConsoleCommands()
    {
        $modules = ['Console' => [
            'path' => $this->applicationRoot]
        ];

        return $this->discoverCommands($modules);
    }

    /**
     * @return array
     */
    public function getCustomCommands()
    {
        $modules = $this->getHelperSet()->get('site')->getModules();

        foreach ($this->disabledModules as $disabledModule) {
            if (array_key_exists($disabledModule, $modules)) {
                unset($modules[$disabledModule]);
            }
        }

        return $this->discoverCommands($modules);
    }

    /**
     * @param $modules
     * @return array
     */
    private function discoverCommands($modules)
    {
        $commands = [];
        foreach ($modules as $module => $extension) {
            if ($module === 'Console') {
                $directory = sprintf(
                    '%s/src/Command',
                    $extension['path']
                );
            } else {
                $directory = sprintf(
                    '%s/%s/src/Command',
                    $modules = $this->getHelperSet()->get('drupal')->getRoot(),
                    $extension->getPath()
                );
            }

            if (is_dir($directory)) {
                $commands = array_merge($commands, $this->extractCommands($directory, $module));
            }
        }

        return $commands;
    }

    /**
     * @param $directory
     * @param $module
     * @return array
     */
    private function extractCommands($directory, $module)
    {
        $finder = new Finder();
        $finder->files()
            ->name('*Command.php')
            ->in($directory)
            ->depth('< 2');

        $commands = [];

        foreach ($finder as $file) {
            $className = sprintf(
                'Drupal\%s\Command\%s',
                $module,
                $file->getBasename('.php')
            );
            $command = $this->validateCommand($className, $module);
            if ($command) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    /**
     * @param $className
     * @param $module
     * @return mixed
     */
    private function validateCommand($className, $module)
    {
        if (!class_exists($className)) {
            return;
        }

        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->isAbstract()) {
            return;
        }

        if (!$reflectionClass->isSubclassOf('Drupal\\Console\\Command\\Command')) {
            return;
        }

        if (!$this->getHelperSet()->get('drupal')->isInstalled() && $reflectionClass->isSubclassOf('Drupal\\Console\\Command\\ContainerAwareCommand')) {
            return;
        }

        if ($reflectionClass->getConstructor()->getNumberOfRequiredParameters() > 0) {
            if ($module != 'Console') {
                $this->getHelperSet()->get('translator')->addResourceTranslationsByModule($module);
            }
            $command = $reflectionClass->newInstance($this->getHelperSet());
        } else {
            $command = $reflectionClass->newInstance();
        }
        $command->setModule($module);

        if ($reflectionClass->isSubclassOf('Drupal\\Console\\Command\\ContainerAwareCommand')) {
            $kernel = $this->getHelperSet()->get('kernel')->getKernel();
            $container = $kernel->getContainer();
            $command->setContainer($container);
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'commandDiscovery';
    }
}
