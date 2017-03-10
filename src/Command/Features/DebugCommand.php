<?php

/**
* @file
* Contains \Drupal\Console\Command\Features\DebugCommand.
*/

namespace Drupal\Console\Command\Features;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\FeatureTrait;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Symfony\Component\Console\Command\Command;

/**
 * @DrupalCommand(
 *     extension = "features",
 *     extensionType = "module"
 * )
 */

class DebugCommand extends Command
{
    use CommandTrait;
    use FeatureTrait;

    protected function configure()
    {
        $this
            ->setName('features:debug')
            ->setDescription($this->trans('commands.features.debug.description'))
            ->addArgument(
                'bundle',
                InputArgument::OPTIONAL,
                $this->trans('commands.features.debug.arguments.bundle')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $bundle= $input->getArgument('bundle');

        $tableHeader = [
            $this->trans('commands.features.debug.messages.bundle'),
            $this->trans('commands.features.debug.messages.name'),
            $this->trans('commands.features.debug.messages.machine_name'),
            $this->trans('commands.features.debug.messages.status'),
            $this->trans('commands.features.debug.messages.state'),
        ];

        $tableRows = [];
       
        $features = $this->getFeatureList($io, $bundle);
   
        foreach ($features as $feature) {
            $tableRows[] = [$feature['bundle_name'],$feature['name'], $feature['machine_name'], $feature['status'],$feature['state']];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }
}
