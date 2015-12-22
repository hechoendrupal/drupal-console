<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\InstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Drupal\Core\Config\PreExistingConfigException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class InstallCommand extends ContainerAwareCommand
{
    protected $moduleInstaller;

    protected function configure()
    {
        $this
            ->setName('module:install')
            ->setDescription($this->trans('commands.module.install.description'))
            ->addArgument('module', InputArgument::IS_ARRAY, $this->trans('commands.module.install.options.module'))
            ->addOption('overwrite-config', '', InputOption::VALUE_NONE, $this->trans('commands.module.install.options.overwrite-config'));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');

        if (!$module) {
            $moduleList = [];

            $modules = system_rebuild_module_data();
            foreach ($modules as $moduleId => $module) {
                if ($module->status == 1) {
                    continue;
                }

                $moduleList[$moduleId] = $module->info['name'];
            }

            while (true) {
                $moduleName = $io->choiceNoList(
                    $this->trans('commands.module.install.questions.module'),
                    array_keys($moduleList),
                    null,
                    true
                );

                if (empty($moduleName)) {
                    break;
                }

                $moduleListInstall[] = $moduleName;

                if (array_search($moduleName, $moduleListInstall, true) >= 0) {
                    unset($moduleList[$moduleName]);
                }
            }

            $input->setArgument('module', $moduleListInstall);
        }

        $overwrite_config = $input->getOption('overwrite-config');

        $input->setOption('overwrite-config', $overwrite_config);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $extension_config = $this->getConfigFactory()->getEditable('core.extension');

        $this->moduleInstaller = $this->getModuleInstaller();

        // Get info about modules available
        $module_data = system_rebuild_module_data();

        $modules = $input->getArgument('module');
        $overwrite_config = $input->getOption('overwrite-config');

        $module_list = array_combine($modules, $modules);

        // Determine if some module request is missing
        if ($missing_modules = array_diff_key($module_list, $module_data)) {
            $io->error(
                sprintf(
                    $this->trans('commands.module.install.messages.missing'),
                    implode(', ', $modules),
                    implode(', ', $missing_modules)
                )
            );

            return true;
        }

        // Only process currently uninstalled modules.
        $installed_modules = $extension_config->get('module') ?: array();
        if (!$module_list = array_diff_key($module_list, $installed_modules)) {
            $io->warning($this->trans('commands.module.install.messages.nothing'));
            return;
        }

        // Calculate dependencies and missing dependencies
        $dependencies = array();
        $missing_dependencies = array();
        while (list($module) = each($module_list)) {
            foreach (array_keys($module_data[$module]->requires) as $dependency) {
                if (!isset($module_data[$dependency])) {
                    $missing_dependencies[] = $dependency;
                }

                // Skip already installed modules.
                if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
                    $module_list[$dependency] = $dependency;
                    $dependencies[] = $dependency;
                }
            }
        }

        // Error if there are missing dependencies
        if (!empty($missing_dependencies)) {
            $io->error(
                sprintf(
                    $this->trans('commands.module.install.messages.missing-dependencies'),
                    implode(', ', $modules),
                    implode(', ', $missing_dependencies)
                )
            );

            return true;
        }

        // Confirm if user want to install dependencies uninstalled
        if ($dependencies) {
            if (!$io->confirm(
                sprintf(
                    $this->trans('commands.module.install.messages.dependencies'),
                    implode(', ', $dependencies)
                ),
                false
            )) {
                return;
            }
        }

        // Installing modules
        try {
            // Install the modules.
            $this->moduleInstaller->install($module_list);
            system_rebuild_module_data();
            $io->success(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', array_merge($modules, $dependencies))
                )
            );
        } catch (PreExistingConfigException $e) {
            $this->overwriteConfig($e, $module_list, $modules, $dependencies, $overwrite_config, $io);

            return;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return;
        }

        // Run cache rebuild to see changes in Web UI
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function overwriteConfig(PreExistingConfigException $e, $module_list, $modules, $dependencies, $overwrite_config, DrupalStyle $io)
    {
        if ($overwrite_config) {
            $io->info($this->trans('commands.module.install.messages.config-conflict-overwrite'));
        } else {
            $io->info($this->trans('commands.module.install.messages.config-conflict'));
        }

        $configObjects = $e->getConfigObjects();
        foreach (current($configObjects) as $config) {
            $io->info($config);
            $config = $this->getConfigFactory()->getEditable($config);
            $config->delete();
        }

        if (!$overwrite_config) {
            return;
        }

        // Try to reinstall modules
        try {
            // Install the modules.
            $this->moduleInstaller->install($module_list);
            system_rebuild_module_data();
            $io->info(
              sprintf(
                $this->trans('commands.module.install.messages.success'),
                implode(', ', array_merge($modules, $dependencies))
              )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return;
        }
    }
}
