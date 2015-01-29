<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ModuleInstallCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ModuleInstallCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('module:install')
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

    $modules = array_filter(array_map('trim', explode( ",", $module)));

    $module_list = array_combine($modules, $modules);

    // Determine if some module request is missing
    if ($missing_modules = array_diff_key($module_list, $module_data)) {
      $output->writeln('[+] <error>' . sprintf($this->trans('commands.module.install.messages.missing'), implode(", ", $modules), implode(", ", $missing_modules)) .'</error>');
      return TRUE;
    }

    // Only process currently uninstalled modules.
    $installed_modules = $extension_config->get('module') ?: array();
    if (!$module_list = array_diff_key($module_list, $installed_modules)) {
      $output->writeln('[+] <info>' . $this->trans('commands.module.install.messages.nothing') .'</info>');
      return TRUE;
    }

    // Calculate dependencies and missing dependencies
    $dependencies = array();
    $misssing_dependencies = array();
    while (list($module) = each($module_list)) {
      foreach (array_keys($module_data[$module]->requires) as $dependency) {
        if (!isset($module_data[$dependency])) {
          $misssing_dependencies[] = $dependency;
        }

        // Skip already installed modules.
        if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
          $module_list[$dependency] = $dependency;
          $dependencies[] = $dependency;
        }
      }
    }

    // Error if there are missing dependencies
    if(!empty($misssing_dependencies)) {
      $output->writeln('[+] <error>' . sprintf($this->trans('commands.module.install.messages.missing-dependencies'), implode(", ", $modules), implode(", ", $misssing_dependencies)) .'</error>');
      return TRUE;
    }

    // Confirm if user want to install dependencies uninstalled
    if($dependencies) {
      $dialog = $this->getDialogHelper();
      if (!$dialog->askConfirmation(
        $output,
        $dialog->getQuestion(sprintf($this->trans('commands.module.install.messages.dependencies'), implode(", ", $dependencies)), n) ,
        false
      )) {
      return;
     }
    }

    // Installing modules
    try {
      // Install the modules.
      $moduleInstaller->install($module_list);

      $output->writeln('[+] <info>' . sprintf($this->trans('commands.module.install.messages.sucess'), implode(", ", $modules)) .'</info>');
    }
    catch (\Exception $e) {
      $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
      return;
    }
  }
}
