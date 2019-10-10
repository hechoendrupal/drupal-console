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
                $this->trans('commands.module.install.options.composer')
            )
            ->setAliases(['moi']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        if (!$module) {
            $module = $this->modulesQuestion();
            $input->setArgument('module', $module);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $composer = $input->getOption('composer');

        $this->site->loadLegacyFile('/core/includes/bootstrap.inc');

        // When --composer is specified, build a command to Composer require
        // all the needed modules in one go. This will just download the
        // modules from the composer endpoint, not do any 'installation', in
        // Drupal terminology.
        if ($composer) {
            $composer_package_list = [];
            $module_list = [];
            foreach ($module as $item) {
                // Decompose each module item passed on the command line into
                // Composer-ready elements.
                $temp = explode('/', $item);
                if (count($temp) === 1) {
                    $package_namespace = 'drupal';
                    $package = $temp[0];
                } else {
                    $package_namespace = $temp[0];
                    $package = $temp[1];
                }
                $temp = explode(':', $package);
                if (count($temp) === 1) {
                    $package_constraint = null;
                } else {
                    $package = $temp[0];
                    $package_constraint = $temp[1];
                }

                // Add the Composer argument.
                $temp = "$package_namespace/$package";
                if (isset($package_constraint)) {
                    $temp .= ':' . $package_constraint;
                }
                $composer_package_list[] = $temp;

                // Add the module to the list of those to be Drupal-installed.
                if ($package_namespace === 'drupal') {
                    $module_list[] = $package;
                }
            }
            $module = $module_list;

            // Run the Composer require command.
            $command = array_merge(['composer', 'require'], $composer_package_list);
            $this->getIo()->info('Executing... ' . implode(' ', $command));
            $processBuilder = new ProcessBuilder([]);
            $processBuilder->setWorkingDirectory($this->appRoot);
            $processBuilder->setArguments($command);
            $processBuilder->inheritEnvironmentVariables();
            $process = $processBuilder->getProcess();
            $process->setTty(true);
            $process->run();

            if ($process->isSuccessful()) {
                $this->getIo()->info(
                    sprintf(
                        $this->trans('commands.module.install.messages.download-with-composer'),
                        implode(', ', $composer_package_list)
                    )
                );
            } else {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.module.install.messages.not-installed-with-composer'),
                        implode(', ', $composer_package_list)
                    )
                );
                throw new \RuntimeException($process->getErrorOutput());
            }
        }

        // Build the list of modules to be installed, skipping those that are
        // installed already.
        $resultList = $this->downloadModules($module, $latest);
        $invalidModules = $resultList['invalid'];
        $unInstalledModules = $resultList['uninstalled'];

        if ($invalidModules) {
            foreach ($invalidModules as $invalidModule) {
                unset($module[array_search($invalidModule, $module)]);
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.module.install.messages.invalid-name'),
                        $invalidModule
                    )
                );
            }
        }

        // If no modules need to be installed, warn and exit.
        if (!$unInstalledModules) {
            $this->getIo()->warning($this->trans('commands.module.install.messages.nothing'));
            return 0;
        }

        // Install the needed modules.
        try {
            $this->getIo()->comment(
                sprintf(
                    $this->trans('commands.module.install.messages.installing'),
                    implode(', ', $unInstalledModules)
                )
            );

            drupal_static_reset('system_rebuild_module_data');

            $this->moduleInstaller->install($unInstalledModules, true);
            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', $unInstalledModules)
                )
            );
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }

        $this->site->removeCachedServicesFile();
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
