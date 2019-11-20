<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\ShellProcess;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCommand
 *
 * @package Drupal\Console\Command\Module
 */
class InstallCommand extends Command
{
    use ProjectDownloadTrait;
    use ModuleTrait;

    /**
     * @var ShellProcess
     */
    protected $shellProcess;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var ModuleInstaller
     */
    protected $moduleInstaller;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * InstallCommand constructor.
     *
     * @param ShellProcess    $shellProcess
     * @param Site            $site
     * @param Validator       $validator
     * @param ModuleInstaller $moduleInstaller
     * @param ChainQueue      $chainQueue
     */
    public function __construct(
        ShellProcess $shellProcess,
        Site $site,
        Validator $validator,
        ModuleInstallerInterface $moduleInstaller,
        ChainQueue $chainQueue
    ) {
        $this->shellProcess = $shellProcess;
        $this->site = $site;
        $this->validator = $validator;
        $this->moduleInstaller = $moduleInstaller;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('module:install')
            ->setDescription($this->trans('commands.module.install.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.install.arguments.module')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.install.options.composer'),
                'yes'
            )
            ->addOption(
                'latest',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.latest')
            )
            ->setAliases(['moi']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('module')) {
            $module = $this->modulesQuestion();
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = mb_strtolower($input->getOption('composer')[0]) !== 'n';

        // Fully qualify the modules required on the command line.
        $requestedModules = $this->composerQualifyModuleNames($input->getArgument('module'));
        $missingModules = $this->validator->getMissingModules(array_keys($requestedModules));

        // Manage the modules missing from the file system with Composer
        // require.
        if ($missingModules) {
            $modulesToDownload = [];
            $packagesToComposerRequire = [];
            foreach ($requestedModules as $drupalModuleName => $composerPackageName) {
                if (in_array($drupalModuleName, $missingModules)) {
                    $modulesToDownload[] = $drupalModuleName;
                    $packagesToComposerRequire[] = $composerPackageName;
                }
            }
            if ($composer) {
                $this->getIo()->comment(
                    sprintf(
                        $this->trans('commands.module.install.messages.download'),
                        implode(', ', $modulesToDownload)
                    )
                );
                $this->composerRequirePackages($packagesToComposerRequire);
            } else {
                $this->getIo()->warning(
                    sprintf(
                        $this->trans('commands.module.install.messages.cannot-download'),
                        implode(', ', $modulesToDownload)
                    )
                );
            }
        }

        // Build the list of modules to be Drupal-installed, skipping those
        // that are installed already.
        $modulesToInstall = $this->validator->getUninstalledModules(array_keys($requestedModules));
        if ($dependencies = $this->calculateDependencies($modulesToInstall)) {
            $this->getIo()->comment(
                sprintf(
                    $this->trans('commands.module.install.messages.install-dependencies'),
                    implode(', ', $dependencies)
                )
            );
        }
        $modulesToInstall = array_merge($modulesToInstall, $dependencies);

        // If any module is missing from the file system at this stage, throw
        // an error message and exit, since they cannot be installed.
        $missingModules = $this->validator->getMissingModules($modulesToInstall);
        if ($missingModules) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.module.install.messages.missing'),
                    implode(', ', $missingModules)
                )
            );
            return 1;
        }

        // Info about modules installed already.
        $modulesAlreadyInstalled = array_diff(array_keys($requestedModules), $modulesToInstall);
        if ($modulesAlreadyInstalled) {
            $this->getIo()->comment(
                sprintf(
                    $this->trans('commands.module.install.messages.already-installed'),
                    implode(', ', $modulesAlreadyInstalled)
                )
            );
        }

        // If no modules need to be Drupal-installed, warn and exit.
        if (!$modulesToInstall) {
            $this->getIo()->warning($this->trans('commands.module.install.messages.nothing'));
            return 0;
        }

        // Drupal-install the needed modules.
        try {
            $this->getIo()->comment(
                sprintf(
                    $this->trans('commands.module.install.messages.installing'),
                    implode(', ', $modulesToInstall)
                )
            );
            $this->moduleInstaller->install($modulesToInstall, true);
            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', $modulesToInstall)
                )
            );
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());
            return 1;
        }

        // Rebuild caches.
        $this->site->removeCachedServicesFile();
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
