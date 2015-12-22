<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\UninstallCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class UninstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('module:uninstall')
            ->setDescription($this->trans('commands.module.uninstall.description'))
            ->addArgument('module', InputArgument::REQUIRED, $this->trans('commands.module.uninstall.options.module'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io =  new DrupalStyle($input, $output);

        $extension_config = $this->getConfigFactory()->getEditable('core.extension');

        $moduleInstaller = $this->getModuleInstaller();

        // Get info about modules available
        $module_data = system_rebuild_module_data();

        $module = $input->getArgument('module');

        $modules = array_filter(array_map('trim', explode(',', $module)));

        $module_list = array_combine($modules, $modules);

        // Determine if some module request is missing
        if ($missing_modules = array_diff_key($module_list, $module_data)) {
            $io->error(
              sprintf(
                $this->trans('commands.module.uninstall.messages.missing'),
                implode(', ', $modules),
                implode(', ', $missing_modules)
              )
            );

            return true;
        }

        // Only process currently installed modules.
        $installed_modules = $extension_config->get('module') ?: array();
        if (!$module_list = array_intersect_key($module_list, $installed_modules)) {
            $io->info($this->trans('commands.module.uninstall.messages.nothing'));

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
            $io->error(
              sprintf(
                $this->trans('commands.module.uninstall.messages.dependents'),
                implode(', ', $modules),
                implode(', ', $dependents)
              )
            );

            return true;
        }

        // Installing modules
        try {
            // Install the modules.
            $moduleInstaller->uninstall($module_list);

            $io->info(
              sprintf(
                $this->trans('commands.module.uninstall.messages.success'),
                implode(', ', $modules)
              )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return;
        }

        // Run cache rebuild to see changes in Web UI
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }
}
