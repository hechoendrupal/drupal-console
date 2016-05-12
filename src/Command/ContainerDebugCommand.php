<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ContainerDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ContainerDebugCommand
 * @package Drupal\Console\Command
 */
class ContainerDebugCommand extends BaseCommand
{
    use ContainerAwareCommandTrait;
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
        $drupalContainer = $this->getDrupalContainer();
        //$services = $this->getServices();


        $tableHeader = [
            $this->trans('commands.container.debug.messages.service_id'),
            $this->trans('commands.container.debug.messages.class_name')
        ];

        $tableRows = [];
        foreach ($drupalContainer->getServiceIds() as $serviceId) {
            $service = $drupalContainer->get($serviceId);
            $class = get_class($service);
            $tableRows[] = [$serviceId, $class];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }
}
