<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;

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
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * InstallCommand constructor.
     *
     * @param Site            $site
     * @param Validator       $validator
     * @param ModuleInstaller $moduleInstaller
     * @param DrupalApi       $drupalApi
     * @param Manager         $extensionManager
     * @param $appRoot
     * @param ChainQueue      $chainQueue
     */
    public function __construct(
        Site $site,
        Validator $validator,
        ModuleInstallerInterface $moduleInstaller,
        DrupalApi $drupalApi,
        Manager $extensionManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->validator = $validator;
        $this->moduleInstaller = $moduleInstaller;
        $this->drupalApi = $drupalApi;
        $this->extensionManager = $extensionManager;
        $this->appRoot = $appRoot;
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
                'latest',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.latest')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.uninstall.options.composer')
            )
            ->setAliases(['moi']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        if (!$module) {
            $module = $this->modulesQuestion($io);
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $composer = $input->getOption('composer');

        $this->site->loadLegacyFile('/core/includes/bootstrap.inc');

        // check module's requirements
        $this->moduleRequirement($module, $io);

        if ($composer) {
            foreach ($module as $moduleItem) {
                $command = sprintf(
                    'composer show drupal/%s ',
                    $moduleItem
                );

                $processBuilder = new ProcessBuilder([]);
                $processBuilder->setWorkingDirectory($this->appRoot);
                $processBuilder->setArguments(explode(" ", $command));
                $process = $processBuilder->getProcess();
                $process->setTty('true');
                $process->run();

                if ($process->isSuccessful()) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.module.install.messages.download-with-composer'),
                            $moduleItem
                        )
                    );
                } else {
                    $io->error(
                        sprintf(
                            $this->trans('commands.module.install.messages.not-installed-with-composer'),
                            $moduleItem
                        )
                    );
                    throw new \RuntimeException($process->getErrorOutput());
                }
            }

            $unInstalledModules = $module;
        } else {
            $resultList = $this->downloadModules($io, $module, $latest);

            $invalidModules = $resultList['invalid'];
            $unInstalledModules = $resultList['uninstalled'];

            if ($invalidModules) {
                foreach ($invalidModules as $invalidModule) {
                    unset($module[array_search($invalidModule, $module)]);
                    $io->error(
                        sprintf(
                            $this->trans('commands.module.install.messages.invalid-name'),
                            $invalidModule
                        )
                    );
                }
            }

            if (!$unInstalledModules) {
                $io->warning($this->trans('commands.module.install.messages.nothing'));

                return 0;
            }
        }

        try {
            $io->comment(
                sprintf(
                    $this->trans('commands.module.install.messages.installing'),
                    implode(', ', $unInstalledModules)
                )
            );

            drupal_static_reset('system_rebuild_module_data');

            $this->moduleInstaller->install($unInstalledModules, true);
            $io->success(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', $unInstalledModules)
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->site->removeCachedServicesFile();
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
