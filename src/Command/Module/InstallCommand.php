<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class InstallCommand
 * @package Drupal\Console\Command\Module
 */
class InstallCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ProjectDownloadTrait;

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

        $this->get('site')->loadLegacyFile('core/includes/bootstrap.inc');

        if ($composer) {
            foreach ($module as $moduleItem) {
                $command = sprintf(
                    'composer show drupal/%s ',
                    $moduleItem
                );

                $shellProcess = $this->get('shell_process');
                if ($shellProcess->exec($command)) {
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

            $moduleInstaller = $this->getDrupalService('module_installer');
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

        $this->get('chain_queue')->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
