<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\ContainerDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContainerDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('container:debug')
          ->setDescription($this->trans('commands.container.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $services = $this->getServices();
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(
            [
            $this->trans('commands.container.debug.messages.service_id'),
            $this->trans('commands.container.debug.messages.class_name'),
            ]
        );
        $table->setlayout($table::LAYOUT_COMPACT);
        foreach ($services as $serviceId) {
            $service = $this->getContainer()->get($serviceId);
            $class = get_class($service);
            $table->addRow([$serviceId, $class]);
        }
        $table->render($output);
    }
}
