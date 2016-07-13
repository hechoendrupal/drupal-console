<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\ExecuteCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;

class ExecuteCommand extends Command
{
    use ContainerAwareCommandTrait;

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
        $module_handler = $this->getDrupalService('module_handler');
        $lock = $this->getDrupalService('lock');

        // Try to acquire cron lock.
        if (!$lock->acquire('cron', 900.0)) {
            $io->warning($this->trans('commands.cron.execute.messages.lock'));
            return;
        }

        if (in_array('all', $modules)) {
            $modules = $module_handler->getImplementations('cron');
        }

        foreach ($modules as $module) {
            if ($module_handler->implementsHook($module, 'cron')) {
                $io->info(
                    sprintf(
                        $this->trans('commands.cron.execute.messages.executing-cron'),
                        $module
                    )
                );
                try {
                    $module_handler->invoke($module, 'cron');
                } catch (\Exception $e) {
                    watchdog_exception('cron', $e);
                    $io->error($e->getMessage());
                }
            } else {
                $io->warning(
                    sprintf(
                        $this->trans('commands.cron.execute.messages.module-invalid'),
                        $module
                    )
                );
            }
        }

        // Set last time cron was executed
        \Drupal::state()->set('system.cron_last', REQUEST_TIME);

         // Release cron lock.
        $lock->release('cron');

        $this->get('chain_queue')->addCommand('cache:rebuild', ['cache' => 'all']);

        $io->success($this->trans('commands.cron.execute.messages.success'));
    }
}
