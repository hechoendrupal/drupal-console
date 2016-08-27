<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UninstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Style\DrupalStyle;


use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Utils\ShellProcess;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Core\Config\ConfigFactoryInterface;

class UninstallCommand extends Command
{
    use CommandTrait;
    use ProjectDownloadTrait;


        /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var ShellProcess
     */
    protected $shellProcess;

    /**
     * @var ModuleInstaller
     */
    protected $moduleInstaller;

    /**
      * @var DrupalApi
      */
    protected $drupalApi;

    /**
      * @var ConfigFactoryInterface
      */
    protected $configFactory;

    /**
     * InstallCommand constructor.
     * @param ChainQueue $chainQueue
     * @param ShellProcess $shellProcess
     * @param ModuleInstaller $moduleInstaller
     * @param DrupalApi $drupalApi
     */
    public function __construct(
      ChainQueue $chainQueue,
      ShellProcess $shellProcess,
      ModuleInstaller $moduleInstaller,
      DrupalApi $drupalApi,
      ConfigFactoryInterface $config_factory
    ) {
        $this->chainQueue = $chainQueue;
        $this->shellProcess = $shellProcess;
        $this->moduleInstaller = $moduleInstaller;
        $this->drupalApi = $drupalApi;
        $this->configFactory = $config_factory;
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
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.uninstall.options.force')
            )
            ->addOption(
                'composer',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.uninstall.options.composer')
            );
    }
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $module = $input->getArgument('module');

        if (!$module) {
            $module = $this->modulesUninstallQuestion($io);
            $input->setArgument('module', $module);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io =  new DrupalStyle($input, $output);
        $composer = $input->getOption('composer');
        $module = $input->getArgument('module');

        $this->drupalApi->loadLegacyFile('/core/modules/system/system.module');
        $coreExtension = $this->configFactory->getEditable('core.extension');
        $moduleInstaller = $this->moduleInstaller;

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

                $shellProcess = $this->shellProcess;
                if ($shellProcess->exec($command)) {
                    $io->success(
                        sprintf(
                            $this->trans('commands.module.uninstall.messages.composer-success'),
                            $moduleItem
                        )
                    );
                }
            }
        }

        if ($missingModules = array_diff_key($moduleList, $moduleData)) {
            $io->error(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.missing'),
                    implode(', ', $module),
                    implode(', ', $missingModules)
                )
            );

            return 1;
        }

        $installedModules = $coreExtension->get('module') ?: array();
        if (!$moduleList = array_intersect_key($moduleList, $installedModules)) {
            $io->info($this->trans('commands.module.uninstall.messages.nothing'));

            return 0;
        }

        if (!$force = $input->getOption('force')) {
            $dependencies = [];
            while (list($module) = each($moduleList)) {
                foreach (array_keys($moduleData[$module]->required_by) as $dependency) {
                    if (isset($installedModules[$dependency]) && !isset($moduleList[$dependency]) && $dependency != $profile) {
                        $dependencies[] = $dependency;
                    }
                }
            }

            if (!empty($dependencies)) {
                $io->error(
                    sprintf(
                        $this->trans('commands.module.uninstall.messages.dependents'),
                        implode(', ', $module),
                        implode(', ', $dependencies)
                    )
                );

                return 1;
            }
        }

        try {
            $moduleInstaller->uninstall($moduleList);

            $io->info(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.success'),
                    implode(', ', $moduleList)
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }
}
