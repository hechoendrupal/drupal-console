<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UpdateCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ShellProcess;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
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
     * @var Site
     */
    protected $site;

    /**
     * UpdateCommand constructor.
     *
     * @param ShellProcess $shellProcess
     * @param Manager              $extensionManager
     * @param Validator            $validator
     * @param Site            $site
     */
    public function __construct(
        ShellProcess $shellProcess,
        Manager $extensionManager,
        Validator $validator,
        Site $site
    ) {
        $this->shellProcess = $shellProcess;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->site = $site;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('module:update')
            ->setDescription($this->trans('commands.module.update.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.update.arguments.module')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.update.options.composer'),
                'yes'
            )
            ->addOption(
                'simulate',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.update.options.simulate')
            )
            ->addOption(
                'run-updates',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.update.options.run-updates'),
                'yes'
            )
            ->setAliases(['moup']);
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
        $modules = $input->getArgument('module');
        $composer = mb_strtolower($input->getOption('composer')[0]) !== 'n';
        $runUpdates = mb_strtolower($input->getOption('run-updates')[0]) !== 'n';
        $simulate = $input->getOption('simulate');

        if (!$modules) {
            $this->getIo()->error(
                $this->trans('commands.module.update.messages.no-module')
            );
            return 1;
        }

        // Fully qualify the modules required on the command line.
        $requestedModules = $this->composerQualifyModuleNames($modules);
        $missingModules = $this->validator->getMissingModules(array_keys($requestedModules));
        $allCoreModules = $this->extensionManager->discoverModules()->showInstalled()->showUninstalled()->showCore()->getList(true);
        $allInstalledModules = $this->extensionManager->discoverModules()->showInstalled()->showCore()->showNoCore()->getList(true);

        // Unless --composer=no is specified, use Composer to update the
        // modules.
        if ($composer) {
            $modulesToDownload = [];
            $modulesToUpdate = [];
            $modulesToSkipMissing = [];
            $modulesToSkipCore = [];
            $packagesToComposerRequire = [];
            $packagesToComposerUpdate = [];
            foreach ($requestedModules as $drupalModuleName => $composerPackageName) {
                // Skip if the module is missing.
                if (in_array($drupalModuleName, $missingModules)) {
                    $modulesToSkipMissing[] = $drupalModuleName;
                    continue;
                }
                // Skip if the module is a core one.
                if (in_array($drupalModuleName, $allCoreModules)) {
                    $modulesToSkipCore[] = $drupalModuleName;
                    continue;
                }
                $forUpdate = $this->getModuleComposerComponents($composerPackageName);
                // Composer-require the module if a constraint is specified.
                if ($forUpdate['constraint']) {
                    $modulesToDownload[] = $drupalModuleName;
                    $packagesToComposerRequire[] = $composerPackageName;
                    $packagesToComposerUpdate[] = $forUpdate['namespace'] . '/' . $forUpdate['name'];
                } else {
                    $modulesToUpdate[] = $drupalModuleName;
                    $packagesToComposerUpdate[] = $composerPackageName;
                }
            }

            if ($modulesToSkipMissing) {
                $this->getIo()->comment(
                    sprintf(
                        $this->trans('commands.module.update.messages.missing'),
                        implode(', ', $modulesToSkipMissing)
                    )
                );
            }

            if ($modulesToSkipCore) {
                $this->getIo()->comment(
                    sprintf(
                        $this->trans('commands.module.update.messages.cannot-update-core'),
                        implode(', ', $modulesToSkipCore)
                    )
                );
            }

            if ($packagesToComposerRequire) {
                $this->getIo()->comment(
                    sprintf(
                        $this->trans('commands.module.update.messages.download-required'),
                        implode(', ', $modulesToDownload)
                    )
                );
                $this->composerRequirePackages($packagesToComposerRequire);
            }

            if ($packagesToComposerUpdate) {
                $this->composerUpdatePackages($packagesToComposerUpdate, true, $simulate);
            }
        }

        // Rebuild caches.
        $this->site->removeCachedServicesFile();

        // We cannot use ChainQueue here to process 'update:execute', since any
        // update that requires accessing newly installed modules will fail
        // given that Drupal core statically cached discovered module files
        // prior to any Composer-require. So we run it as a separate process
        // to allow re-discovering of any module added by Composer.
        if ($runUpdates) {
            $command = [$this->shellProcess->findExecutable('drupal'), 'update:execute'];
            $isInteractive = $this->getIo()->getInput()->isInteractive();
            if (!$isInteractive) {
                $command[] = '--no-interaction';
                $this->shellProcess->execTty($command, true);
            } else {
                $this->shellProcess->execTty($command);
            }
        }
    }
}
