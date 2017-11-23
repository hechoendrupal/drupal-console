<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\BreakpointsCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\breakpoint\BreakpointManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * @DrupalCommand(
 *     extension = "breakpoint",
 *     extensionType = "module"
 * )
 */
class BreakpointsCommand extends Command
{
    /**
     * @var BreakpointManagerInterface
     */
    protected $breakpointManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * BreakpointsCommand constructor.
     *
     * @param BreakpointManagerInterface $breakpointManager
     * @param string                     $appRoot
     */
    public function __construct(
        BreakpointManagerInterface $breakpointManager = null,
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
            ->setName('debug:breakpoints')
            ->setDescription($this->trans('commands.debug.breakpoints.description'))
            ->addArgument(
                'group',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.breakpoints.options.group-name')
            )->setAliases(['dbre']);
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
            $this->trans('commands.debug.breakpoints.messages.name'),
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

        $projectPath = drupal_get_path($typeExtension, $group);

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
