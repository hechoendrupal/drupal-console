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
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class ExecuteCommand extends Command
{
    use CommandTrait;

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface  */
    protected $moduleHandler;

    /** @var \Drupal\Core\Lock\LockBackendInterface  */
    protected $lock;

    /** @var  \Drupal\Core\State\State */
    protected $state;

    /** @var  \Drupal\Console\Utils\ChainQueue */
    protected $chainQueue;

    /**
     * DebugCommand constructor.
     * @param $moduleHandler
     * @param $lock
     * @param $state
     * @param $chainQueue
     */
    public function __construct($moduleHandler, $lock, $state, $chainQueue ) {
        $this->moduleHandler = $moduleHandler;
        $this->lock = $lock;
        $this->state = $state;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

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
    }
}
