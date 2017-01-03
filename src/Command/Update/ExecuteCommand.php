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
                InputArgument::REQUIRED,
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
        $this->module = $input->getArgument('module');
        $this->update_n = $input->getArgument('update-n');

        $this->site->loadLegacyFile('/core/includes/install.inc');
        $this->site->loadLegacyFile('/core/includes/update.inc');

        drupal_load_updates();
        update_fix_compatibility();
        $updates = update_get_update_list();
        $this->checkUpdates($io);
        $maintenance_mode = $this->state->get('system.maintenance_mode', false);

        if (!$maintenance_mode) {
            $io->info($this->trans('commands.site.maintenance.description'));
            $this->state->set('system.maintenance_mode', true);
        }

        $this->runUpdates($io, $updates);
        $this->runPostUpdates($io);

        if (!$maintenance_mode) {
            $this->state->set('system.maintenance_mode', false);
            $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));
        }

        $this->chainQueue
            ->addCommand('cache:rebuild', ['cache' => 'all']);
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     */
    private function checkUpdates(DrupalStyle $io)
    {
        if ($this->module != 'all') {
            if (!isset($updates[$this->module])) {
                $io->error(
                    sprintf(
                        $this->trans('commands.update.execute.messages.no-module-updates'),
                        $this->module
                    )
                );
                return;
            } else {
                // filter to execute only a specific module updates
                $updates = [$this->module => $updates[$this->module]];

                if ($this->update_n && !isset($updates[$this->module]['pending'][$this->update_n])) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.update.execute.messages.module-update-function-not-found'),
                            $this->module,
                            $this->update_n
                        )
                    );
                }
            }
        }
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param $updates
     */
    private function runUpdates(DrupalStyle $io, $updates)
    {
        foreach ($updates as $module_name => $module_updates) {
            $extension = $this->extensionManager->getModule($module_name);
            if (!$extension) {
                $extension = $this->extensionManager->getProfile($module_name);
            }
            if ($extension) {
                $this->site
                    ->loadLegacyFile($extension->getPath() . '/'. $module_name . '.install', false);
            }

            foreach ($module_updates['pending'] as $update_number => $update) {
                if ($this->module != 'all' && $this->update_n !== null && $this->update_n != $update_number) {
                    continue;
                }

                if ($this->update_n > $module_updates['start']) {
                    $io->info(
                        $this->trans('commands.update.execute.messages.executing-required-previous-updates')
                    );
                }

                for ($update_index=$module_updates['start']; $update_index<=$update_number; $update_index++) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.update.execute.messages.executing-update'),
                            $update_index,
                            $module_name
                        )
                    );

                    try {
                        $this->moduleHandler->invoke($module_name, 'update_'  . $update_index);
                    } catch (\Exception $e) {
                        watchdog_exception('update', $e);
                        $io->error($e->getMessage());
                    }

                    drupal_set_installed_schema_version($module_name, $update_index);
                }
            }
        }
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     */
    private function runPostUpdates(DrupalStyle $io)
    {
        $postUpdates = $this->postUpdateRegistry->getPendingUpdateInformation();
        foreach ($postUpdates as $module_name => $module_updates) {
            foreach ($module_updates['pending'] as $update_number => $update) {
                if ($this->module != 'all' && $this->update_n !== null && $this->update_n != $update_number) {
                    continue;
                }

                if ($this->update_n > $module_updates['start']) {
                    $io->info(
                        $this->trans('commands.update.execute.messages.executing-required-previous-updates')
                    );
                }
                for ($update_index=$module_updates['start']; $update_index<=$update_number; $update_index++) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.update.execute.messages.executing-update'),
                            $update_index,
                            $module_name
                        )
                    );

                    try {
                        $function = sprintf(
                            '%s_post_update_%s',
                            $module_name,
                            $update_index
                        );
                        drupal_flush_all_caches();
                        update_invoke_post_update($function);
                    } catch (\Exception $e) {
                        watchdog_exception('update', $e);
                        $io->error($e->getMessage());
                    }
                }
            }
        }
    }
}
