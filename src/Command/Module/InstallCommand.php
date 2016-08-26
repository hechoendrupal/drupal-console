<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Style\DrupalStyle;



use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Utils\ShellProcess;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class InstallCommand
 * @package Drupal\Console\Command\Module
 */
class InstallCommand extends Command
{
    use CommandTrait;
    use ProjectDownloadTrait;
    use ModuleTrait;

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
      DrupalApi $drupalApi
    ) {
        $this->chainQueue = $chainQueue;
        $this->shellProcess = $shellProcess;
        $this->moduleInstaller = $moduleInstaller;
        $this->drupalApi = $drupalApi;
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
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.module.install.options.latest')
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

        $this->drupalApi->loadLegacyFile('core/includes/bootstrap.inc');

        // check module's requirements
        $this->moduleRequirement($module);

        if ($composer) {

            // checking if the directory has a composer.json

            if ( basename( getcwd() ) == "web" || basename( getcwd() ) == "docroot")
            {
                $cd = "cd ../; ";
                $cd_back = "cd ". getcwd();
            } else
            {
              $cd = "";
              $cd_back = "";
            }

            foreach ($module as $moduleItem) {
                $command = sprintf(
                    $cd . 'composer show drupal/%s; ' . $cd_back,
                    $moduleItem
                );

                $shellProcess = $this->shellProcess;
                //@TODO:exec() should halt the run on errors
                if ($proc = $shellProcess->exec($command)) {
                    $io->info(
                        sprintf(
                            'Module %s was downloaded with Composer.',
                            $moduleItem
                        )
                    );
                } else {
                    $io->error(
                        sprintf(
                            'Module %s seems not to be installed with Composer. Halting.',
                            $moduleItem
                        )
                    );

                    return 0;
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
                            'Invalid module name: %s',
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

            $moduleInstaller = $this->moduleInstaller;
            drupal_static_reset('system_rebuild_module_data');

            $moduleInstaller->install($unInstalledModules, true);
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

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
