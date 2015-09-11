<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\views\Entity\View;
use Drupal\Component\Serialization\Yaml;

class CronExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:execute')
            ->setDescription($this->trans('commands.views.execute.description'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $module_handler = $this->getModuleHandler();

        if ($module_handler->implementsHook($module, 'cron')) {
            try {
                $this->moduleHandler->invoke($module, 'cron');
            } catch (\Exception $e) {
                watchdog_exception('cron', $e);
                $output->writeln(
                    '<error>' .
                    $e->getMessage().
                    '</error>'
                );
            }
        } else {
            $output->writeln(
                '<error>' .
                sprintf(
                    $this->trans('commands.cron.execute.messages.module-invalid'),
                $module
                )
                . '</error>'
            );
        }

        $table->setHeaders(
            [
                $this->trans('commands.cron.debug.messages.module'),
            ]
        );

        foreach ($module_handler->getImplementations('cron') as $module) {
            $table->addRow(
                [
                    $module,
                ]
            );
        }

        $table->render($output);
    }
}
