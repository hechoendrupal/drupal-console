<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ModuleCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Site;
use Drupal\Console\Core\Utils\ConfigurationManager;

class ModuleCommand extends Command
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var Site
     */
    protected $site;

    /**
     * ChainDebugCommand constructor.
     *
     * @param ConfigurationManager $configurationManager
     * @param Site                 $site
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        Site $site
    ) {
        $this->configurationManager = $configurationManager;
        $this->site = $site;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('debug:module')
            ->setDescription($this->trans('commands.debug.module.description'))
            ->addArgument(
                'module',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                $this->trans('commands.debug.module.module')
            )
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.module.options.status')
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.module.options.type')
            )
            ->setAliases(['dm']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->site->loadLegacyFile('/core/modules/system/system.module');

        $status = strtolower($input->getOption('status'));
        $type = strtolower($input->getOption('type'));
        $modules = $input->getArgument('module');

        if ($status == 'installed') {
            $status = 1;
        } elseif ($status == 'uninstalled') {
            $status = 0;
        } else {
            $status = -1;
        }

        if ($type == 'core') {
            $type = 'core';
        } elseif ($type == 'no-core') {
            $type = '';
        } else {
            $type = null;
        }

        $tableHeader = [
          $this->trans('commands.debug.module.messages.id'),
          $this->trans('commands.debug.module.messages.name'),
          $this->trans('commands.debug.module.messages.package'),
          $this->trans('commands.debug.module.messages.version'),
          $this->trans('commands.debug.module.messages.schema-version'),
          $this->trans('commands.debug.module.messages.status'),
          $this->trans('commands.debug.module.messages.origin'),
        ];

        $tableRows = $this->getModules($status, $type, $modules);

        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }

    /**
     * Get the module info
     * @param $status
     * @param $type
     * @param $modules
     *
     * @return array
     */
    private function getModules($status, $type, $modules) {

        $result = [];
        $modulesData = system_rebuild_module_data();

        if(!$modules) {
            $modules = array_keys($modulesData) ;
        }

        foreach ($modules as $module) {
            $moduleData = $modulesData[strtolower($module)];

            if(!$moduleData) {
                continue;
            }

            if ($status >= 0 && $status != $moduleData->status) {
                continue;
            }

            if ($type !== null && $type !== $moduleData->origin) {
                continue;
            }

            $module_status = ($moduleData->status) ? $this->trans('commands.debug.module.messages.installed') : $this->trans('commands.debug.module.messages.uninstalled');
            $module_origin = ($moduleData->origin) ? $moduleData->origin : 'no core';
            $schema_version = (drupal_get_installed_schema_version($module)!= -1?drupal_get_installed_schema_version($module): '');

            $result [] = [
                $module,
                $moduleData->info['name'],
                $moduleData->info['package'],
                $moduleData->info['version'],
                $schema_version,
                $module_status,
                $module_origin,
            ];
        }

        return $result;
    }
}
