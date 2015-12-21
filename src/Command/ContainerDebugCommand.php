<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ContainerDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ContainerDebugCommand
 * @package Drupal\Console\Command
 */
class ContainerDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('container:debug')
            ->setDescription($this->trans('commands.container.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $services = $this->getServices();

        $tableHeader = [
            $this->trans('commands.container.debug.messages.service_id'),
            $this->trans('commands.container.debug.messages.class_name')
        ];

        $tableRows = [];
        foreach ($services as $serviceId) {
            $service = $this->getContainer()->get($serviceId);
            $class = get_class($service);
            $tableRows[] = [$serviceId, $class];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }
}
