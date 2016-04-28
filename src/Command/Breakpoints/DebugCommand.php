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
use Drupal\Component\Serialization\Yaml;


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
                'group',
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

        $group = $input->getArgument('group');
        if (!$group) {
            $groups = $this->getAllBreakpoints();

            $tableHeader = [
                $this->trans('commands.breakpoints.debug.messages.name'),
            ];

            $io->table($tableHeader, $groups, 'compact');
        }
        else {
            $breakPointData = $this->getBreakpointByName($group);

            foreach ($breakPointData as $key => $breakPoint ) {
                $io->comment($key);
                $io->writeln(Yaml::encode($breakPoint));

            }

        }
    }

    private function getAllBreakpoints()
    {
        $breakpointsManager = $this->getService('breakpoint.manager');
        $groups =  array_keys($breakpointsManager->getGroups());
        
        return $groups;
    }

    /**
     * @param $group    String
     */
    private function getBreakpointByName($group)
    {
            $breakpointsManager = \Drupal::service('breakpoint.manager');
            $typeExtension = implode(',', array_values($breakpointsManager->getGroupProviders($group)));

            if($typeExtension == 'theme'){
                $projectPath = drupal_get_path('theme', $group);
            }
            if($typeExtension == 'module'){
                $projectPath = drupal_get_path('module', $group);
            }

            return  Yaml::decode(file_get_contents($projectPath . '/' .  $group . '.breakpoints.yml'));
    }
}
