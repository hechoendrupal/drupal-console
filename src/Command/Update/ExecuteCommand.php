<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\ExecuteCommand.
 */

namespace Drupal\Console\Command\Update;

use Drupal\Console\Command\Shared\UpdateTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Console\Utils\Site;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;

class ExecuteCommand extends Command
{
    use UpdateTrait;

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
            ->setAliases(['upex'])
            ->enableMaintenance();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->module = $input->getArgument('module');
        $this->update_n = (int)$input->getArgument('update-n');

        $this->site->loadLegacyFile('/core/includes/install.inc');
        $this->site->loadLegacyFile('/core/includes/update.inc');

        drupal_load_updates();

        $start = $this->getUpdates($this->module!=='all'?$this->module:null);
        $updates = update_resolve_dependencies($start);
        $allowUpdate = false;
        $assumeYes = $input->getOption('yes');

        if (!$this->checkUpdates($start, $updates)) {
            if ($this->module === 'all') {
                $this->getIo()->info(
                    sprintf(
                        $this->trans(
                            'commands.update.execute.messages.no-pending-updates'
                        )
                    )
                );
            } else {
                $this->getIo()->info(
                    sprintf(
                        $this->trans(
                            'commands.update.execute.messages.no-module-updates'
                        ),
                        $this->module
                    )
                );
            }
            $this->getIo()->info('');
        } else {
            $updateList = update_get_update_list();
            $this->showUpdateTable($this->module === 'all' ?  $updateList: $updateList[$this->module], $this->trans('commands.update.execute.messages.pending-updates'));

            $allowUpdate = $assumeYes || $this->getIo()->confirm(
                $this->trans('commands.update.execute.questions.update'),
                true
            );
        }

        // Handle Post update to execute
        $allowPostUpdate = false;
        if(!$postUpdates = $this->postUpdateRegistry->getPendingUpdateInformation()) {
            $this->getIo()->info(
                $this->trans('commands.update.execute.messages.no-pending-post-updates')
            );
        } else {
            $this->showPostUpdateTable($postUpdates, $this->trans('commands.update.execute.messages.pending-post-updates'));
            $allowPostUpdate = $assumeYes || $this->getIo()->confirm(
                $this->trans('commands.update.execute.questions.post-update'),
                true
            );
        }

        if($allowUpdate) {
            try {
                $this->runUpdates(
                    $updates
                );
            } catch (\Exception $e) {
                watchdog_exception('update', $e);
                $this->getIo()->error($e->getMessage());
                return 1;
            }
        }

        if($allowPostUpdate) {
            $this->runPostUpdates($postUpdates);
        }

        if($allowPostUpdate || $allowUpdate) {
            $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
        }

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
     * @param array       $updates
     */
    private function runUpdates(
        array $updates
    ) {
        $this->getIo()->info(
            $this->trans('commands.update.execute.messages.executing-required-previous-updates')
        );

        foreach ($updates as $function => $update) {
            if (!$update['allowed']) {
                continue;
            }

            if ($this->module !== 'all' && $update['number'] > $this->update_n) {
                break;
            }

            $this->getIo()->comment(
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
     * @param array $postUpdates
     * @return bool
     */
    private function runPostUpdates($postUpdates)
    {
        if(!$postUpdates) {
            return 0;
        }

        foreach ($postUpdates as $module => $updates) {
            foreach ($updates['pending'] as $updateName => $update) {
                $this->getIo()->info(
                    sprintf(
                        $this->trans('commands.update.execute.messages.executing-post-update'),
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
                $this->postUpdateRegistry->registerInvokedUpdates([$function]);
            }
        }

        $this->chainQueue->addCommand('update:entities');

        return 1;
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
        $context['sandbox'] = [];
        do {
            if (function_exists($function)) {
                $return = $function($context['sandbox']);

                if (is_string($return)) {
                    $this->getIo()->info(
                        "  ".$return
                    );
                }

                if (isset($context['sandbox']['#finished']) && ($context['sandbox']['#finished'] < 1)) {
                    $this->getIo()->info(
                        '  Processed '.number_format($context['sandbox']['#finished'] * 100, 2).'%'
                    );
                }
            }
        } while (isset($context['sandbox']['#finished']) && ($context['sandbox']['#finished'] < 1));

        return true;
    }
}
