<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UninstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Extension\Manager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Utils\Site;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\Config\ConfigFactoryInterface;

class UninstallCommand extends Command
{
    use ProjectDownloadTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var ModuleInstaller
     */
    protected $moduleInstaller;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * InstallCommand constructor.
     *
     * @param Site            $site
     * @param ModuleInstaller $moduleInstaller
     * @param ChainQueue      $chainQueue
     * @param ConfigFactory   $configFactory
     * @param Manager         $extensionManager
     */
    public function __construct(
        Site $site,
        ModuleInstallerInterface $moduleInstaller,
        ChainQueue $chainQueue,
        ConfigFactoryInterface $configFactory,
        Manager $extensionManager
    ) {
        $this->site = $site;
        $this->moduleInstaller = $moduleInstaller;
        $this->chainQueue = $chainQueue;
        $this->configFactory = $configFactory;
        $this->extensionManager = $extensionManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('module:uninstall')
            ->setDescription($this->trans('commands.module.uninstall.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.uninstall.questions.module')
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.uninstall.options.force')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.uninstall.options.composer')
            )
            ->setAliases(['mou']);
    }
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');

        if (!$module) {
            $module = $this->modulesUninstallQuestion();
            $input->setArgument('module', $module);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $input->getOption('composer');
        $module = $input->getArgument('module');

        $this->site->loadLegacyFile('/core/modules/system/system.module');

        $coreExtension = $this->configFactory->getEditable('core.extension');

        // Get info about modules available
        $moduleData = system_rebuild_module_data();
        $moduleList = array_combine($module, $module);

        if ($composer) {
            //@TODO: check with Composer if the module is previously required in composer.json!
            foreach ($module as $moduleItem) {
                $command = sprintf(
                    'composer remove drupal/%s ',
                    $moduleItem
                );

                $shellProcess = $this->get('shell_process');
                if ($shellProcess->exec($command)) {
                    $this->getIo()->success(
                        sprintf(
                            $this->trans('commands.module.uninstall.messages.composer-success'),
                            $moduleItem
                        )
                    );
                }
            }
        }

        if ($missingModules = array_diff_key($moduleList, $moduleData)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.missing'),
                    implode(', ', $module),
                    implode(', ', $missingModules)
                )
            );

            return 1;
        }

        $installedModules = $coreExtension->get('module') ?: [];
        if (!$moduleList = array_intersect_key($moduleList, $installedModules)) {
            $this->getIo()->info($this->trans('commands.module.uninstall.messages.nothing'));

            return 0;
        }

        if (!$force = $input->getOption('force')) {

            // Get a list of installed profiles that will be excluded when calculating
            // the dependency tree.
            if (\Drupal::hasService('profile_handler')) {
                // #1356276 adds the profile_handler service but it hasn't been committed
                // to core yet so we need to check if it exists.
                $profiles = \Drupal::service('profile_handler')->getProfileInheritance();
            } else {
                $profiles[drupal_get_profile()] = [];
            }

            $dependencies = [];
            while (list($module) = each($moduleList)) {
                foreach (array_keys($moduleData[$module]->required_by) as $dependency) {
                    if (isset($installedModules[$dependency]) && !isset($moduleList[$dependency]) && (!array_key_exists($dependency, $profiles))) {
                        $dependencies[] = $dependency;
                    }
                }
            }

            if (!empty($dependencies)) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.module.uninstall.messages.dependents'),
                        implode('", "', $moduleList),
                        implode(', ', $dependencies)
                    )
                );

                return 1;
            }
        }

        try {
            $this->moduleInstaller->uninstall($moduleList);

            $this->getIo()->info(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.success'),
                    implode(', ', $moduleList)
                )
            );

            $this->getIo()->comment(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.composer-success'),
                    implode(', ', $moduleList),
                    false
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
