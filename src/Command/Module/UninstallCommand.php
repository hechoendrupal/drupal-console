<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UninstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Style\DrupalStyle;

class UninstallCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ProjectDownloadTrait;

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

        $this->get('site')->loadLegacyFile('/core/modules/system/system.module');
        $coreExtension = $this->getDrupalService('config.factory')->getEditable('core.extension');
        $moduleInstaller = $this->getDrupalService('module_installer');

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

        $this->get('chain_queue')->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }
}
