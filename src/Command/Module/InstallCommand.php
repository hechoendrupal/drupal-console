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

        // check module's requirements
        $this->moduleRequirement($module);

        if ($composer) {
            foreach ($module as $moduleItem) {
                $command = sprintf(
                    'composer show drupal/%s ',
                    $moduleItem
                );

                $processBuilder = new ProcessBuilder([]);
                $processBuilder->setWorkingDirectory($this->appRoot);
                $processBuilder->setArguments(explode(' ', $command));
                $process = $processBuilder->getProcess();
                $process->setTty('true');
                $process->run();

                if ($process->isSuccessful()) {
                    $this->getIo()->info(
                        sprintf(
                            $this->trans('commands.module.install.messages.download-with-composer'),
                            $moduleItem
                        )
                    );
                } else {
                    $this->getIo()->error(
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

            if (!$unInstalledModules) {
                $this->getIo()->warning($this->trans('commands.module.install.messages.nothing'));

                return 0;
            }
        }

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

    /**
     * Verify that install requirements for a list of modules are met.
     *
     * @param string[]    $module
     *   List of modules to verify.
     *
     * @throws \Exception
     *   When one or more requirements are not met.
     */
    public function moduleRequirement(array $module)
    {
        $modules_data = system_rebuild_module_data();

        // for unmet requirements recursively.
        $fail = false;
        foreach ($module as $module_name) {
            module_load_install($module_name);
            if ($requirements = \Drupal::moduleHandler()->invoke($module_name, 'requirements', ['install'])) {
                foreach ($requirements as $requirement) {
                    if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
                        $this->getIo()->errorLite("Module '{$module_name}' cannot be installed: {$requirement['title']} | {$requirement['value']}");
                        $this->getIo()->newLine();
                        $fail = true;
                    }
                }
            }

            $module_data = $modules_data[$module_name];

            // Check the core compatibility.
            if ($module_data->info['core'] != \Drupal::CORE_COMPATIBILITY) {
                $versionCore = \Drupal::CORE_COMPATIBILITY;
                $this->getIo()->errorLite("This version is not compatible with Drupal {$versionCore} and should be replaced.");
                $this->getIo()->newLine();
            }

            // Ensure this module is compatible with the currently installed version of PHP.
            if (version_compare(phpversion(), $module_data->info['php']) < 0) {
                $required = $module_data->info['php'] . (substr_count($module_data->info['php'], '.') < 2 ? '.*' : '');
                $phpversion = phpversion();
                $this->getIo()->errorLite("This module requires PHP version {$required} and is incompatible with PHP version {$phpversion}.");
                $this->getIo()->newLine();
                $fail = true;
            }

            // If this module requires other modules, add them to the array.
            foreach ($module_data->requires as $dependency => $version) {
                // dependency exist.
                if (!isset($modules_data[$dependency])) {
                    $dependencyName = ucfirst($dependency);
                    $this->getIo()->errorLite("{$dependencyName} missing.");
                    $this->getIo()->newLine();
                    $fail = true;
                }

                elseif (empty($modules_data[$dependency]->hidden)) {
                    $name = $modules_data[$dependency]->info['name'];
                    // dependency's version.
                    if ($incompatible_version = drupal_check_incompatibility($version, str_replace(\Drupal::CORE_COMPATIBILITY . '-', '', $modules_data[$dependency]->info['version']))) {
                        $dependencyName = $name . $incompatible_version;
                        $dependencyVersion = $modules_data[$dependency]->info['version'];
                        $this->getIo()->errorLite("{$dependencyName} incompatible with version {$dependencyVersion}.");
                        $this->getIo()->newLine();
                        $fail = true;
                    }

                    // version of Drupal core.
                    elseif ($modules_data[$dependency]->info['core'] != \Drupal::CORE_COMPATIBILITY) {
                        $this->getIo()->errorLite("{$name} incompatible with this version of Drupal core.");
                        $this->getIo()->newLine();
                        $fail = true;
                    }
                }
            }
        }
        if ($fail) {
            throw new \Exception('Some module install requirements are not met.');
        }
    }
}
