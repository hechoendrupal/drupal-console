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
            $modules = $this->getSite()->getModules(true, false, true, true, true, true);

            while (true) {
                $moduleName = $io->choiceNoList(
                    $this->trans('commands.module.install.questions.module'),
                    $modules,
                    null,
                    true
                );

                if (empty($moduleName)) {
                    break;
                }

                $moduleList[] = $moduleName;

                if (array_search($moduleName, $moduleList, true) >= 0) {
                    unset($modules[array_search($moduleName, $modules)]);
                }
            }

            $input->setArgument('module', $moduleList);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $modules = $input->getArgument('module');
        $overwriteConfig = $input->getOption('overwrite-config');

        $validator = $this->getValidator();
        $moduleInstaller = $this->getModuleInstaller();

        $invalidModules = $validator->getInvalidModules($modules);
        if ($invalidModules) {
            $io->error(
                sprintf(
                    $this->trans('commands.module.install.messages.missing'),
                    implode(', ', $modules),
                    implode(', ', $invalidModules)
                )
            );

            return;
        }

        $unInstalledModules = $validator->getUninstalledModules($modules);
        if (!$unInstalledModules) {
            $io->warning($this->trans('commands.module.install.messages.nothing'));

            return;
        }

        $dependencies = $this->calculateDependencies($unInstalledModules);

        $missingDependencies = $validator->getInvalidModules($dependencies);
        if ($missingDependencies) {
            $io->error(
                sprintf(
                    $this->trans('commands.module.install.messages.missing-dependencies'),
                    implode(', ', $modules),
                    implode(', ', $missingDependencies)
                )
            );

            return true;
        }

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

        $moduleList = array_merge($unInstalledModules, $dependencies);

        try {
            $moduleInstaller->install($moduleList);
            $io->success(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', $moduleList)
                )
            );
        } catch (PreExistingConfigException $e) {
            $this->overwriteConfig($io, $e, $moduleList, $overwriteConfig);

            return;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return;
        }

        // Run cache rebuild to see changes in Web UI
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    protected function calculateDependencies($modules)
    {
        $this->getDrupalHelper()->loadLegacyFile('/core/modules/system/system.module');

        $dependencies = [];
        $moduleList = system_rebuild_module_data();
        $validator = $this->getValidator();

        foreach ($modules as $moduleName) {
            $module = $moduleList[$moduleName];

            $dependencies = array_unique(
                array_merge(
                    $dependencies,
                    $validator->getUninstalledModules(
                        array_keys($module->requires)?:[]
                    )
                )
            );
        }

        return $dependencies;
    }

    protected function overwriteConfig(
        DrupalStyle $io,
        PreExistingConfigException $e,
        $moduleList,
        $overwriteConfig
    ) {
        if ($overwriteConfig) {
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

        if (!$overwriteConfig) {
            return;
        }

        try {
            $moduleInstaller = $this->getModuleInstaller();
            $moduleInstaller->install($moduleList);
            $io->info(
                sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', $moduleList)
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return;
        }
    }
}
