<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DebugCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;


class DebugCommand extends ContainerAwareCommand
{

    private $urlRepo = "https://packagist.drupal-composer.org/packages/drupal/";

    protected function configure()
    {
        $this
            ->setName('module:debug')
            ->setDescription($this->trans('commands.module.debug.description'))
            ->addArgument(
                'module',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                $this->trans('commands.module.debug.module')
            )
            ->addOption('status', null, InputOption::VALUE_OPTIONAL, $this->trans('commands.module.debug.options.status'))
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, $this->trans('commands.module.debug.options.type'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->getDrupalHelper()->loadLegacyFile('/core/modules/system/system.module');

        $status = $input->getOption('status');
        $type = $input->getOption('type');
        $modules = $input->getArgument('module');

        if ($modules) {
          foreach ($modules as $module) {
            $url = $this->urlRepo . $module . ".json";
            $data = $this->getHelperSet()->get('httpClient')->getUrlAsJson($url);

            $tableHeader = [
                '<info>'.$data->package->name.'</info>'
            ];
            $tableRows = [];

            $tableRows[] = [
                $data->package->description
            ];

            $tableRows[] = [
                '<comment>'.$this->trans('commands.module.debug.messages.total-downloads').'</comment>',
                $data->package->downloads->total
            ];

            $tableRows[] = [
                '<comment>'.$this->trans('commands.module.debug.messages.total-monthly').'</comment>',
                $data->package->downloads->monthly
            ];

            $tableRows[] = [
                '<comment>'.$this->trans('commands.module.debug.messages.total-daily').'</comment>',
                $data->package->downloads->daily
            ];

            $io->table($tableHeader, $tableRows, 'compact');
          }
          return;
        }

        if (strtolower($status) == 'enabled') {
            $status = 1;
        } elseif (strtolower($status) == 'disabled') {
            $status = 0;
        } else {
            $status = -1;
        }

        if (strtolower($type) == 'core') {
            $type = 'core';
        } elseif (strtolower($type) == 'no-core') {
            $type = '';
        } else {
            $type = null;
        }

        $tableHeader = [
          $this->trans('commands.module.debug.messages.id'),
          $this->trans('commands.module.debug.messages.name'),
          $this->trans('commands.module.debug.messages.status'),
          $this->trans('commands.module.debug.messages.package'),
          $this->trans('commands.module.debug.messages.version'),
          $this->trans('commands.module.debug.messages.schema-version'),
          $this->trans('commands.module.debug.messages.origin'),
        ];

        $tableRows = [];
        $modules = system_rebuild_module_data();
        foreach ($modules as $module_id => $module) {
            if ($status >= 0 && $status != $module->status) {
                continue;
            }

            if ($type !== null && $type !== $module->origin) {
                continue;
            }

            $module_status = ($module->status) ? $this->trans('commands.module.debug.messages.enabled') : $this->trans('commands.module.debug.messages.disabled');
            $schema_version = (drupal_get_installed_schema_version($module_id)!= -1?drupal_get_installed_schema_version($module_id): '');

            $tableRows [] = [
              $module_id,
              $module->info['name'],
              $module_status,
              $module->info['package'],
              $module->info['version'],
              $schema_version,
              $module->origin,
            ];
        }
        $io->table($tableHeader, $tableRows, 'compact');
    }
}
