<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ModuleUninstallCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ModuleUninstallCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
          ->setName('module:uninstall')
          ->setDescription($this->trans('commands.module.install.description'))
          ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.module.install.options.module'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extension_config = $this->getConfigFactory()->getEditable('core.extension');

        $moduleInstaller = $this->getModuleInstaller();

        // Get info about modules available
        $module_data = system_rebuild_module_data();

        $module = $input->getArgument('module');

        $modules = array_filter(array_map('trim', explode(",", $module)));

        $module_list = array_combine($modules, $modules);

        // Determine if some module request is missing
        if ($missing_modules = array_diff_key($module_list, $module_data)) {
            $output->writeln('[+] <error>' . sprintf($this->trans('commands.module.uninstall.messages.missing'),
                implode(", ", $modules), implode(", ", $missing_modules)) . '</error>');
            return true;
        }

        // Only process currently installed modules.
        $installed_modules = $extension_config->get('module') ?: array();
        if (!$module_list = array_intersect_key($module_list, $installed_modules)) {
            $output->writeln('[+] <info>' . $this->trans('commands.module.uninstall.messages.nothing') . '</info>');
            return true;
        }

        // Calculate $dependents
        $dependents = array();
        while (list($module) = each($module_list)) {
            foreach (array_keys($module_data[$module]->required_by) as $dependent) {
                // Skip already uninstalled modules.
                if (isset($installed_modules[$dependent]) && !isset($module_list[$dependent]) && $dependent != $profile) {
                    $dependents[] = $dependent;
                }
            }
        }

        // Error if there are missing dependencies
        if (!empty($dependents)) {
            $output->writeln('[+] <error>' . sprintf($this->trans('commands.module.uninstall.messages.dependents'),
                implode(", ", $modules), implode(", ", $dependents)) . '</error>');
            return true;
        }


        // Installing modules
        try {
            // Install the modules.
            $moduleInstaller->uninstall($module_list);

            $output->writeln('[+] <info>' . sprintf($this->trans('commands.module.uninstall.messages.success'),
                implode(", ", $modules)) . '</info>');
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;
        }
    }
}
