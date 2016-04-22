<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Breakpoints\DebugCommand.
 */

namespace Drupal\Console\Command\Breakpoints;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('breakpoints:debug')
            ->setDescription($this->trans('commands.breakpoints.debug.description'))
            ->addArgument(
                'group-name',
                InputArgument::OPTIONAL,
                $this->trans('commands.breakpoints.debug.options.group-name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $groupName = $input->getArgument('group-name');
        if (!$groupName) {
            $this->getAllBreakpoints($io);
        }
        else {
            $this->getBreakpointsnByName($io, $groupName);
        }
    }

    /**
     * @param $io         DrupalStyle
     */
    private function getAllBreakpoints(DrupalStyle $io)
    {
        $breakpointsManager = $this->getService('breakpoint.manager');
        $groups =  array_keys($breakpointsManager->getGroups());

        $tableHeader = [
            $this->trans('commands.breakpoints.debug.messages.name'),
        ];

        $tableRows = [];
        foreach ($groups as $groupName) {
            $tableRows[] = [$groupName];
        }

        $io->comment($tableRows);
        $io->table($tableHeader, $tableRows, 'compact');
    }

    /**
     * @param $io             DrupalStyle
     * @param $groupName    String
     */
    private function getBreakpointsnByName(DrupalStyle $io, $groupName)
    {
        $breakpointsManager = $this->getService('breakpoint.manager');
        $groups = $breakpointsManager->getBreakpointsByGroup($groupName);

        $breakPointNames = array_keys($groups);

        $tableHeader = [
            $this->trans('commands.breakpoints.debug.messages.name'),
            '<info>'.$groupName.'</info>'
        ];

        $tableRows = [];
        foreach ($breakPointNames as $breakPointName) {
            $tableRows[] = [$breakPointName];
        }

        $io->table($tableHeader,$tableRows , 'compact');

    }
}
