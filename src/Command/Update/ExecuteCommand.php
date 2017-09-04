<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\ExecuteCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;

class ExecuteCommand extends Command
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * @var ModuleHandler
     */
    protected $moduleHandler;

    /**
     * @var UpdateRegistry
     */
    protected $postUpdateRegistry;


    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var String
     */
    private $module;

    /**
     * @var String
     */
    private $update_n;

    /**
     * EntitiesCommand constructor.
     *
     * @param Site           $site
     * @param StateInterface $state
     * @param ModuleHandler  $moduleHandler
     * @param UpdateRegistry $postUpdateRegistry
     * @param Manager        $extensionManager
     * @param ChainQueue     $chainQueue
     */
    public function __construct(
        Site $site,
        StateInterface $state,
        ModuleHandler $moduleHandler,
        UpdateRegistry $postUpdateRegistry,
        Manager $extensionManager,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->state = $state;
        $this->moduleHandler = $moduleHandler;
        $this->postUpdateRegistry = $postUpdateRegistry;
        $this->extensionManager = $extensionManager;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('update:execute')
            ->setDescription($this->trans('commands.update.execute.description'))
            ->addArgument(
                'module',
                InputArgument::OPTIONAL,
                $this->trans('commands.common.options.module'),
                'all'
            )
            ->addArgument(
                'update-n',
                InputArgument::OPTIONAL,
                $this->trans('commands.update.execute.options.update-n'),
                '9000'
            )
            ->setAliases(['upex']);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->module = $input->getArgument('module');
        $this->update_n = (int)$input->getArgument('update-n');

        $this->site->loadLegacyFile('/core/includes/install.inc');
        $this->site->loadLegacyFile('/core/includes/update.inc');

        drupal_load_updates();
        update_fix_compatibility();

        $start = $this->getUpdates($this->module!=='all'?$this->module:null);
        $updates = update_resolve_dependencies($start);
        $dependencyMap = [];
        foreach ($updates as $function => $update) {
            $dependencyMap[$function] = !empty($update['reverse_paths']) ? array_keys($update['reverse_paths']) : [];
        }

        if (!$this->checkUpdates($start, $updates)) {
            if ($this->module === 'all') {
                $io->error(
                    sprintf(
                        $this->trans(
                            'commands.update.execute.messages.no-pending-updates'
                        )
                    )
                );
            } else {
                $io->error(
                    sprintf(
                        $this->trans(
                            'commands.update.execute.messages.no-module-updates'
                        ),
                        $this->module
                    )
                );
            }

            return 1;
        }

        $maintenanceMode = $this->state->get('system.maintenance_mode', false);

        if (!$maintenanceMode) {
            $io->info($this->trans('commands.site.maintenance.description'));
            $this->state->set('system.maintenance_mode', true);
        }

        try {
            $this->runUpdates(
                $io,
                $updates
            );

            // Post Updates are only safe to run after all schemas have been updated.
            if (!$this->getUpdates()) {
                $this->runPostUpdates($io);
            }
        } catch (\Exception $e) {
            watchdog_exception('update', $e);
            $io->error($e->getMessage());
            return 1;
        }

        if (!$maintenanceMode) {
            $this->state->set('system.maintenance_mode', false);
            $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));
        }

        if (!$this->getUpdates()) {
            $this->chainQueue->addCommand('update:entities');
        }

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

        return 0;
    }

    /**
     * @param array $start
     * @param array $updates
     *
     * @return bool true if the selected module/update number exists.
     */
    private function checkUpdates(
        array $start,
        array $updates
    ) {
        if (!$start || !$updates) {
            return false;
        }

        if ($this->module !== 'all') {
            $module = $this->module;
            $hooks = array_keys($updates);
            $hooks = array_map(
                function ($v) use ($module) {
                    return (int)str_replace(
                        $module.'_update_',
                        '',
                        $v
                    );
                },
                $hooks
            );

            if ((int)min($hooks) > (int)$this->update_n) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param DrupalStyle $io
     * @param array       $updates
     */
    private function runUpdates(
        DrupalStyle $io,
        array $updates
    ) {
        $io->info(
            $this->trans('commands.update.execute.messages.executing-required-previous-updates')
        );

        foreach ($updates as $function => $update) {
            if (!$update['allowed']) {
                continue;
            }

            if ($this->module !== 'all' && $update['number'] > $this->update_n) {
                break;
            }

            $io->comment(
                sprintf(
                    $this->trans('commands.update.execute.messages.executing-update'),
                    $update['number'],
                    $update['module']
                )
            );

            $this->moduleHandler->loadInclude($update['module'], 'install');

            $this->executeUpdate(
                $function,
                $context
            );

            drupal_set_installed_schema_version(
                $update['module'],
                $update['number']
            );
        }
    }

    /**
     * @param DrupalStyle $io
     *
     * @return bool
     */
    private function runPostUpdates(DrupalStyle $io)
    {
        $postUpdates = $this->postUpdateRegistry->getPendingUpdateInformation();
        foreach ($postUpdates as $module => $updates) {
            foreach ($updates['pending'] as $updateName => $update) {
                $io->info(
                    sprintf(
                        $this->trans('commands.update.execute.messages.executing-update'),
                        $updateName,
                        $module
                    )
                );

                $function = sprintf(
                    '%s_post_update_%s',
                    $module,
                    $updateName
                );
                drupal_flush_all_caches();
                $this->executeUpdate(
                    $function,
                    $context
                );
            }
        }

        return true;
    }

    protected function getUpdates($module = null)
    {
        $start = $this->getUpdateList();
        if ($module) {
            if (isset($start[$module])) {
                $start = [
                    $module => $start[$module]
                ];
            } else {
                $start = [];
            }
        }

        return $start;
    }

    // Copy of protected \Drupal\system\Controller\DbUpdateController::getModuleUpdates.
    protected function getUpdateList()
    {
        $start = [];
        $updates = update_get_update_list();
        foreach ($updates as $module => $update) {
            $start[$module] = $update['start'];
        }

        return $start;
    }

    private function executeUpdate($function, &$context)
    {
        if (!$context || !array_key_exists('sandbox', $context)) {
            $context['sandbox'] = [];
        }

        if (function_exists($function)) {
            $function($context['sandbox']);
        }

        return true;
    }
}
