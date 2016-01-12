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
use Drupal\Console\Command\ContainerAwareCommand;

class ExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:execute')
            ->setDescription($this->trans('commands.cron.execute.description'))
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                $this->trans('commands.common.options.module')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        $module_handler = $this->getModuleHandler();

        if ($module != 'all') {
            $modules = [$module];
        } else {
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

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
