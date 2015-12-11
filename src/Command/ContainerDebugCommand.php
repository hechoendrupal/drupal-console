<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ContainerDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drupal\Console\Style\DrupalStyle;

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
        $output = new DrupalStyle($input, $output);
        $services = $this->getServices();
        $table = new Table($output);

        $table->setHeaders(
            [
                $this->trans('commands.container.debug.messages.service_id'),
                $this->trans('commands.container.debug.messages.class_name')
            ]
        );
        $table->setStyle('compact');
        foreach ($services as $serviceId) {
            $service = $this->getContainer()->get($serviceId);
            $class = get_class($service);
            $table->addRow([$serviceId, $class]);
        }
        $table->render();
    }
}
