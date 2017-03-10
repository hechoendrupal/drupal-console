<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\ExecuteCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\Site;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;

class ExecuteCommand extends Command
{
    use CommandTrait;

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
                $this->trans('commands.common.options.module')
            )
            ->addArgument(
                'update-n',
                InputArgument::OPTIONAL,
                $this->trans('commands.update.execute.options.update-n')
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $this->module = $input->getArgument('module') ?: 'all';
        $this->update_n = $input->getArgument('update-n');

        $this->site->loadLegacyFile('/core/includes/install.inc');
        $this->site->loadLegacyFile('/core/includes/update.inc');

        drupal_load_updates();
        update_fix_compatibility();
        $updates = update_get_update_list();
        if (!$this->checkUpdates($io, $updates)) {
            return 1;
        }

        $maintenance_mode = $this->state->get('system.maintenance_mode', false);

        if (!$maintenance_mode) {
            $io->info($this->trans('commands.site.maintenance.description'));
            $this->state->set('system.maintenance_mode', true);
        }

        try {
            $complete = $this->runUpdates($io, $updates);

            // Post Updates are only safe to run after all schemas have been updated.
            if ($complete) {
                $this->runPostUpdates($io);
            }
        } catch (\Exception $e) {
            watchdog_exception('update', $e);
            $io->error($e->getMessage());
            return 1;
        }

        if (!$maintenance_mode) {
            $this->state->set('system.maintenance_mode', false);
            $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));
        }

        $this->chainQueue
            ->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param array                                  $updates
     *
     * @return bool true if the selected module/update number exists.
     */
    private function checkUpdates(DrupalStyle $io, array $updates)
    {
        if ($this->module != 'all') {
            if (!isset($updates[$this->module])) {
                $io->error(
                    sprintf(
                        $this->trans('commands.update.execute.messages.no-module-updates'),
                        $this->module
                    )
                );
                return false;
            } else {
                if ($this->update_n && !isset($updates[$this->module]['pending'][$this->update_n])) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.update.execute.messages.module-update-function-not-found'),
                            $this->module,
                            $this->update_n
                        )
                    );
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param array                                  $updates
     *
     * @return bool True if all available updates have been run.
     */
    private function runUpdates(DrupalStyle $io, array $updates)
    {
        if ($this->module != 'all') {
            $complete = count($updates) == 1;
            $updates = [$this->module => $updates[$this->module]];
        } else {
            $complete = true;
        }

        foreach ($updates as $module_name => $module_updates) {
            $extension = $this->extensionManager->getModule($module_name);
            if (!$extension) {
                $extension = $this->extensionManager->getProfile($module_name);
            }
            if ($extension) {
                $this->site
                    ->loadLegacyFile($extension->getPath() . '/'. $module_name . '.install', false);
            }

            if ($this->update_n > $module_updates['start']) {
                $io->info(
                    $this->trans('commands.update.execute.messages.executing-required-previous-updates')
                );
            }

            foreach ($module_updates['pending'] as $update_number => $update) {
                if ($this->module != 'all' && $this->update_n !== null && $this->update_n < $update_number) {
                    return false;
                }

                $io->info(
                    sprintf(
                        $this->trans('commands.update.execute.messages.executing-update'),
                        $update_number,
                        $module_name
                    )
                );

                $this->moduleHandler->invoke($module_name, 'update_'  . $update_number);
                drupal_set_installed_schema_version($module_name, $update_number);
            }
        }

        return $complete;
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     */
    private function runPostUpdates(DrupalStyle $io)
    {
        $postUpdates = $this->postUpdateRegistry->getPendingUpdateInformation();
        foreach ($postUpdates as $module_name => $module_updates) {
            foreach ($module_updates['pending'] as $update_name => $update) {
                $io->info(
                    sprintf(
                        $this->trans('commands.update.execute.messages.executing-update'),
                        $update_name,
                        $module_name
                    )
                );

                $function = sprintf(
                    '%s_post_update_%s',
                    $module_name,
                    $update_name
                );
                drupal_flush_all_caches();
                $context = [];
                update_invoke_post_update($function, $context);
            }
        }
    }
}
