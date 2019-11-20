<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DownloadCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ShellProcess;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;
    use ModuleTrait;

    /**
     * @var ShellProcess
     */
    protected $shellProcess;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * DownloadCommand constructor.
     *
     * @param ShellProcess $shellProcess
     * @param Manager      $extensionManager
     * @param Validator    $validator
     */
    public function __construct(
        ShellProcess $shellProcess,
        Manager $extensionManager,
        Validator $validator
    ) {
        $this->shellProcess = $shellProcess;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription($this->trans('commands.module.download.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                $this->trans('commands.module.download.arguments.module')
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.download.options.path')
            )
            ->addOption(
                'latest',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.download.options.latest')
            )
            ->setAliases(['mod']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fully qualify the modules required on the command line.
        $requestedModules = $this->composerQualifyModuleNames($input->getArgument('module'));
        $missingModules = $this->validator->getMissingModules(array_keys($requestedModules));

        // Inform about modules already downloaded and/or installed.
        $readyModules = array_diff(array_keys($requestedModules), $missingModules);
        if ($readyModules) {
            $allCoreModules = $this->extensionManager->discoverModules()->showInstalled()->showUninstalled()->showCore()->getList(true);
            $allNonCoreInstalledModules = $this->extensionManager->discoverModules()->showInstalled()->showNoCore()->getList(true);
            // Core modules.
            if ($requestedCoreModules = array_intersect($readyModules, $allCoreModules)) {
                $this->getIo()->comment(
                    sprintf(
                        $this->trans('commands.module.download.messages.core'),
                        implode(', ', $requestedCoreModules)
                    )
                );
            }
            // Non-core modules.
            if ($requestedNonCoreModules = array_diff($readyModules, $requestedCoreModules)) {
                // Installed.
                if ($requestedNonCoreInstalledModules = array_intersect($requestedNonCoreModules, $allNonCoreInstalledModules)) {
                    $this->getIo()->comment(
                        sprintf(
                            $this->trans('commands.module.download.messages.already-installed'),
                            implode(', ', $requestedNonCoreInstalledModules)
                        )
                    );
                }
                // Non-installed.
                if ($requestedNonCoreNonInstalledModules = array_diff($requestedNonCoreModules, $requestedNonCoreInstalledModules)) {
                    $this->getIo()->comment(
                        sprintf(
                            $this->trans('commands.module.download.messages.already-downloaded'),
                            implode(', ', $requestedNonCoreNonInstalledModules)
                        )
                    );
                }
            }
        }

        // Build the list of packages to Composer-require.
        $modulesToDownload = [];
        $packagesToComposerRequire = [];
        foreach ($requestedModules as $drupalModuleName => $composerPackageName) {
            if (in_array($drupalModuleName, $missingModules)) {
                $modulesToDownload[] = $drupalModuleName;
                $packagesToComposerRequire[] = $composerPackageName;
            }
        }

        // Return if nothing to do.
        if (!$packagesToComposerRequire) {
            $this->getIo()->warning($this->trans('commands.module.download.messages.nothing'));
            return 0;
        }

        // Run Composer require.
        if (!$this->composerRequirePackages($packagesToComposerRequire)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.module.download.messages.composer-failure'),
                    implode(', ', $modulesToDownload)
                )
            );
            return 1;
        }

        return 0;
    }
}
