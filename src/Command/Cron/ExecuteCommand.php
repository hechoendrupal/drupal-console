<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ExecuteCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\ChainQueue;

class ExecuteCommand extends Command
{
    use CommandTrait;

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
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * DebugCommand constructor.
     * @param ModuleHandlerInterface $moduleHandler
     * @param LockBackendInterface   $lock
     * @param StateInterface         $state
     * @param ChainQueue             $chainQueue
     */
    public function __construct(
        ModuleHandlerInterface $moduleHandler,
        LockBackendInterface $lock,
        StateInterface $state,
        ChainQueue $chainQueue
    ) {
        $this->moduleHandler = $moduleHandler;
        $this->lock = $lock;
        $this->state = $state;
        $this->chainQueue = $chainQueue;
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
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                $this->trans('commands.common.options.module')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $modules = $input->getArgument('module');

        if (!$this->lock->acquire('cron', 900.0)) {
            $io->warning($this->trans('commands.cron.execute.messages.lock'));

            return 1;
        }

        if (in_array('all', $modules)) {
            $modules = $this->moduleHandler->getImplementations('cron');
        }

        foreach ($modules as $module) {
            if (!$this->moduleHandler->implementsHook($module, 'cron')) {
                $io->warning(
                    sprintf(
                        $this->trans('commands.cron.execute.messages.module-invalid'),
                        $module
                    )
                );
                continue;
            }
            try {
                $io->info(
                    sprintf(
                        $this->trans('commands.cron.execute.messages.executing-cron'),
                        $module
                    )
                );
                $this->moduleHandler->invoke($module, 'cron');
            } catch (\Exception $e) {
                watchdog_exception('cron', $e);
                $io->error($e->getMessage());
            }
        }

        $this->state->set('system.cron_last', REQUEST_TIME);
        $this->lock->release('cron');

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);

        $io->success($this->trans('commands.cron.execute.messages.success'));

        return 0;
    }
}
