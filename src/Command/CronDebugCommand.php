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

class CronDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:debug')
            ->setDescription($this->trans('commands.views.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        $module_handler = $this->getModuleHandler();

        $output->writeln(
            '<info>'.
            $this->trans('commands.cron.debug.messages.module-list')
            .'</info>'
        );

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
