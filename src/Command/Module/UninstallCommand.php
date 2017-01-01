<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UninstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Core\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Core\Config\ConfigFactory;

class UninstallCommand extends Command
{
    use CommandTrait;
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
     * InstallCommand constructor.
     *
     * @param Site          $site
     * @param Validator     $validator
     * @param ChainQueue    $chainQueue
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        Site $site,
        ModuleInstaller $moduleInstaller,
        ChainQueue $chainQueue,
        ConfigFactory $configFactory
    ) {
        $this->site = $site;
        $this->moduleInstaller = $moduleInstaller;
        $this->chainQueue = $chainQueue;
        $this->configFactory = $configFactory;
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

        $installedModules = $coreExtension->get('module') ?: [];
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
                        implode('", "', $moduleList),
                        implode(', ', $dependencies)
                    )
                );

                return 1;
            }
        }

        try {
            $this->moduleInstaller->uninstall($moduleList);

            $io->info(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.success'),
                    implode(', ', $moduleList)
                )
            );

            $io->comment(
                sprintf(
                    $this->trans('commands.module.uninstall.messages.composer-success'),
                    implode(', ', $moduleList),
                    false
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
