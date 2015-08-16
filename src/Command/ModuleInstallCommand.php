<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\ModuleInstallCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\Core\Config\PreExistingConfigException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleInstallCommand extends ContainerAwareCommand
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
        $module = $input->getArgument('module');

        if (!$module) {
            $module_list = [];

            $dialog = $this->getDialogHelper();

            $modules = system_rebuild_module_data();
            foreach ($modules as $module_id => $module) {
                if ($module->status == 1) {
                    continue;
                }

                $module_list[$module_id] = $module->info['name'];
            }

            $output->writeln('[+] <info>'.$this->trans('commands.module.install.messages.disabled-modules').'</info>');

            while (true) {
                $module_name = $dialog->askAndValidate(
                    $output,
                    $dialog->getQuestion($this->trans('commands.module.install.questions.module'), ''),
                    function ($module_id) use ($module_list) {
                        if ($module_id == '' || $module_list[$module_id]) {
                            return $module_id;
                        } else {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.module.install.questions.invalid-module'), $module_id)
                            );
                        }
                    },
                    false,
                    '',
                    array_keys($module_list)
                );

                if (empty($module_name)) {
                    break;
                }

                $module_list_install[] = $module_name;

                if (array_search($module_name, $module_list_install, true) >= 0) {
                    unset($module_list[$module_name]);
                }
            }

            $input->setArgument('module', $module_list_install);
        }

        $overwrite_config = $input->getOption('overwrite-config');

        $input->setOption('overwrite-config', $overwrite_config);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extension_config = $this->getConfigFactory()->getEditable('core.extension');

        $this->moduleInstaller = $this->getModuleInstaller();

        // Get info about modules available
        $module_data = system_rebuild_module_data();

        $modules = $input->getArgument('module');
        $overwrite_config = $input->getOption('overwrite-config');

        $module_list = array_combine($modules, $modules);

        // Determine if some module request is missing
        if ($missing_modules = array_diff_key($module_list, $module_data)) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.module.install.messages.missing'),
                    implode(', ', $modules),
                    implode(', ', $missing_modules)
                ).'</error>'
            );

            return true;
        }

        // Only process currently uninstalled modules.
        $installed_modules = $extension_config->get('module') ?: array();
        if (!$module_list = array_diff_key($module_list, $installed_modules)) {
            $output->writeln('[+] <info>'.$this->trans('commands.module.install.messages.nothing').'</info>');

            return true;
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
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.module.install.messages.missing-dependencies'),
                    implode(', ', $modules),
                    implode(', ', $missing_dependencies)
                ).'</error>'
            );

            return true;
        }

        // Confirm if user want to install dependencies uninstalled
        if ($dependencies) {
            $dialog = $this->getDialogHelper();
            if (!$dialog->askConfirmation(
                $output,
                $dialog->getQuestion(
                    sprintf(
                        $this->trans('commands.module.install.messages.dependencies'),
                        implode(', ', $dependencies)
                    ), n
                ),
                false
            )
            ) {
                return;
            }
        }

        // Installing modules
        try {
            // Install the modules.
            $this->moduleInstaller->install($module_list);
            system_rebuild_module_data();
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', array_merge($modules, $dependencies))
                ).'</info>'
            );
        } catch (PreExistingConfigException $e) {
            $this->overwriteConfig($e, $module_list, $modules, $dependencies, $overwrite_config, $output);

            return;
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;
        }
    }

    protected function overwriteConfig(PreExistingConfigException $e, $module_list, $modules, $dependencies, $overwrite_config, $output)
    {
        if ($overwrite_config) {
            $output->writeln('[+] <info>' .  $this->trans('commands.module.install.messages.config-conflict-overwrite') . '</info>');
        } else {
            $output->writeln('[+] <info>' .  $this->trans('commands.module.install.messages.config-conflict') . '</info>');
        }

        $configObjects = $e->getConfigObjects();
        foreach (current($configObjects) as $config) {
            $output->writeln('[-] <info>' . $config . '</info>');
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
            $output->writeln(
                '[+] <info>'.sprintf(
                    $this->trans('commands.module.install.messages.success'),
                    implode(', ', array_merge($modules, $dependencies))
                ).'</info>'
            );
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;
        }
    }
}
