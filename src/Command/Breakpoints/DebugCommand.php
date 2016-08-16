<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Breakpoints\DebugCommand.
 */

namespace Drupal\Console\Command\Breakpoints;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Yaml\Yaml;

class DebugCommand extends Command
{
    use CommandTrait;

    protected $breakpointManager;

    /**
     * DebugCommand constructor.
     * @param $breakpointManager
     */
    public function __construct($breakpointManager) {
        $this->breakpointManager = $breakpointManager;
        parent::__construct();
    }

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
            $groups = array_keys($this->breakpointManager->getGroups());

            $tableHeader = [
                $this->trans('commands.breakpoints.debug.messages.name'),
            ];

            $io->table($tableHeader, $groups, 'compact');
        } else {
            $breakPointData = $this->getBreakpointByName($group);

            foreach ($breakPointData as $key => $breakPoint) {
                $io->comment($key);
                $io->writeln(Yaml::encode($breakPoint));
            }
        }
    }

    /**
     * @param $group    String
     */
    private function getBreakpointByName($group)
    {
        $typeExtension = implode(
            ',',
            array_values($this->breakpointManager->getGroupProviders($group))
        );

        if ($typeExtension == 'theme') {
            $projectPath = drupal_get_path('theme', $group);
        }
        if ($typeExtension == 'module') {
            $projectPath = drupal_get_path('module', $group);
        }

        return Yaml::decode(
            file_get_contents($projectPath . '/' .  $group . '.breakpoints.yml')
        );
    }
}
