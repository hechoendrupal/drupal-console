<?php

/**
* @file
* Contains \Drupal\Console\Command\Features\ImportCommand.
*/

namespace Drupal\Console\Command\Features;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\FeatureTrait;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Command\Command;

/**
 * @DrupalCommand(
 *     extension = "features",
 *     extensionType = "module"
 * )
 */

class ImportCommand extends Command
{
    use FeatureTrait;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('features:import')
            ->setDescription($this->trans('commands.features.import.description'))
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.features.import.options.bundle')
            )
            ->addArgument(
                'packages',
                InputArgument::IS_ARRAY,
                $this->trans('commands.features.import.arguments.packages')
            )->setAliases(['fei']);
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packages = $input->getArgument('packages');
        $bundle = $input->getOption('bundle');
      
        if ($bundle) {
            $packages = $this->getPackagesByBundle($bundle);
        }
      
        $this->getAssigner($bundle);
        $this->importFeature($packages);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $packages = $input->getArgument('packages');
        $bundle = $input->getOption('bundle');
        if (!$packages) {
            // @see Drupal\Console\Command\Shared\FeatureTrait::packageQuestion
            $package = $this->packageQuestion($bundle);
            $input->setArgument('packages', $package);
        }
    }
}
