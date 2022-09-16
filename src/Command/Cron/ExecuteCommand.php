<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ExecuteCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;

class ExecuteCommand extends Command
{
    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * @var LockBackendInterface
     */
    protected $lock;

    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * DebugCommand constructor.
     *
     * @param ModuleHandlerInterface $moduleHandler
     * @param LockBackendInterface   $lock
     * @param StateInterface         $state
     */
    public function __construct(
        ModuleHandlerInterface $moduleHandler,
        LockBackendInterface $lock,
        StateInterface $state
    ) {
        $this->moduleHandler = $moduleHandler;
        $this->lock = $lock;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cron:execute')
            ->setDescription($this->trans('commands.cron.execute.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                $this->trans('commands.common.options.module'),
                ['all']
            )
            ->setAliases(['croe']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $input->getArgument('module');

        if (!$this->lock->acquire('cron', 900.0)) {
            $this->getIo()->warning($this->trans('commands.cron.execute.messages.lock'));

            return 1;
        }

        if (in_array('all', $modules)) {
            $modules = $this->moduleHandler->getImplementations('cron');
        }

        foreach ($modules as $module) {
            if (!$this->moduleHandler->implementsHook($module, 'cron')) {
                $this->getIo()->warning(
                    sprintf(
                        $this->trans('commands.cron.execute.messages.module-invalid'),
                        $module
                    )
                );
                continue;
            }
            try {
                $this->getIo()->info(
                    sprintf(
                        $this->trans('commands.cron.execute.messages.executing-cron'),
                        $module
                    )
                );
                $this->moduleHandler->invoke($module, 'cron');
            } catch (\Exception $e) {
                watchdog_exception('cron', $e);
                $this->getIo()->error($e->getMessage());
            }
        }

        $this->state->set('system.cron_last', \Drupal::time()->getRequestTime());
        $this->lock->release('cron');

        $this->getIo()->success($this->trans('commands.cron.execute.messages.success'));

        return 0;
    }
}
