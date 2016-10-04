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
use Drupal\breakpoint\BreakpointManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * @DrupalCommand(
 *     extension = "breakpoint",
 *     extensionType = "module"
 * )
 */
class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var BreakpointManagerInterface
     */
    protected $breakpointManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * DebugCommand constructor.
     * @param BreakpointManagerInterface $breakpointManager
     * @param string                     $appRoot
     */
    public function __construct(
        BreakpointManagerInterface $breakpointManager,
        $appRoot
    ) {
        $this->breakpointManager = $breakpointManager;
        $this->appRoot = $appRoot;
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
        if ($group) {
            $breakPointData = $this->getBreakpointByName($group);
            foreach ($breakPointData as $key => $breakPoint) {
                $io->comment($key, false);
                $io->block(Yaml::dump($breakPoint));
            }

            return 0;
        }
        $groups = array_keys($this->breakpointManager->getGroups());

        $tableHeader = [
            $this->trans('commands.breakpoints.debug.messages.name'),
        ];

        $io->table($tableHeader, $groups, 'compact');

        return 0;
    }

    /**
     * @param $group    String
     * @return mixed
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

        $extensionFile = sprintf(
            '%s/%s/%s.breakpoints.yml',
            $this->appRoot,
            $projectPath,
            $group
        );

        return Yaml::parse(
            file_get_contents($extensionFile)
        );
    }
}
