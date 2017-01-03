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

class ImportCommand extends Command
{
    use CommandTrait;
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
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.features.import.options.packages')
            )
            ->addArgument('packages', InputArgument::IS_ARRAY, $this->trans('commands.features.import.arguments.packages'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $packages = $input->getArgument('packages');
        $bundle = $input->getOption('bundle');
      
        if ($bundle) {
            $packages = $this->getPackagesByBundle($bundle);
        }
      
        $this->getAssigner($bundle);
        $this->importFeature($io, $packages);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $packages = $input->getArgument('packages');
        $bundle = $input->getOption('bundle');
        
        if (!$packages && !$bundle) {
            // @see Drupal\Console\Command\Shared\FeatureTrait::packageQuestion
            $bundle = $this->packageQuestion($io);
            $input->setArgument('packages', $bundle);
        }
    }
}
