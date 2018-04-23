<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\HookCommand.
 */

namespace Drupal\Console\Command\Debug;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Component\Utility;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class HookCommand.
 *
 * @package Drupal\Console\Command\Debug
 */
class HookCommand extends Command
{
    /**
     * @var Manager $extensionManager
     */
    protected $extensionManager = null;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * @var array
     */
    protected $hooks = [];

    /**
     * HookCommand constructor.
     *
     * @param Manager $extensionManager
     * @param ModuleHandlerInterface $moduleHandler
     *
     */
    public function __construct(
        Manager $extensionManager,
        ModuleHandlerInterface $moduleHandler
    ) {
        $this->extensionManager = $extensionManager;
        $this->moduleHandler = $moduleHandler;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:hook')
            ->setDescription($this->trans('commands.debug.hook.description'))
            ->setAliases(['dbh']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->findHooks();
        $tableHeader = [
            $this->trans('commands.debug.hook.messages.name'),
        ];

        $this->getIo()->table($tableHeader, $this->getHooks(), 'compact');
        return 0;
    }

    protected function findHooks()
    {
        $modules = $this->getAllModules();
        $moduleInstances = [];
        foreach ($modules as $module) {
            $moduleInstance = $this->extensionManager->getModule($module);
            if (empty($moduleInstance)) {
                continue;
            }
            $moduleInstances[$module] = $moduleInstance;
            $this->moduleHandler->setModuleList([$module => $moduleInstance]);
            $this->findHooksInApiFile($moduleInstance);

        }
        $this->getHooksFromHookInfo($moduleInstances);
    }

    /**
     * Get module of all modules in the system.
     *
     * @return array
     *   Module list.
     */
    protected function getAllModules()
    {
        return $this->extensionManager->discoverModules()
                    ->showInstalled()
                    ->showUninstalled()
                    ->showNoCore()
                    ->showCore()
                    ->getList(true);
    }

    /**
     * Gets gooks from api file of the module.
     *
     * @param $moduleInstance
     *   Module instance.
     */
    protected function findHooksInApiFile($moduleInstance)
    {
        $finder = new Finder();
        $files = $finder->files()
            ->name("{$moduleInstance->info['name']}.api.php")
            ->in($moduleInstance->getPath());

        foreach ($files as $file) {
            $functions = $this->getFileFunctions($file->getPathname());
            foreach ($functions as $function) {
                if (Unicode::strpos($function, 'hook')) {
                    $this->addHook($function);
                }
            }

        }
    }

    /**
     * Get names of all functions from file.
     *
     * @param string $filePath
     *   File path
     * @return array
     *   Functions list.
     */
    protected function getFileFunctions($filePath)
    {
        $source = file_get_contents($filePath);
        $tokens = token_get_all($source);

        $functions = [];
        $nextStringIsFunc = false;
        foreach($tokens as $token) {
            switch($token[0]) {
                case T_FUNCTION:
                    $nextStringIsFunc = true;
                    break;

                case T_STRING:
                    if ($nextStringIsFunc) {
                        $nextStringIsFunc = false;
                        $functions[] = $token[1];
                    }
                    break;
            }
        }

        return $functions;
    }

    /**
     * @param $modules
     */
    protected function getHooksFromHookInfo($modules)
    {
        $this->moduleHandler->setModuleList($modules);
        foreach(array_keys($this->moduleHandler->getHookInfo()) as $hook) {
            $this->addHook('hook_' . $hook);
        }
    }

    /**
     * Add hook.
     *
     * @param string $value
     *   Hook name.
     */
    protected function addHook($value)
    {
        $this->hooks[] = $value;
    }

    protected function getHooks()
    {
        return $this->hooks;
    }
}
