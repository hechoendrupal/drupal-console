<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\PathCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;

class PathCommand extends Command
{
    use ModuleTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PathCommand constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(Manager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('module:path')
            ->setDescription($this->trans('commands.module.path.description'))
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                $this->trans('commands.module.path.arguments.module')
            )
            ->addOption(
                'absolute',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.path.options.absolute')
            )->setAliases(['mop']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');

        $fullPath = $input->getOption('absolute');

        $module = $this->extensionManager->getModule($module);

        $io->info(
            $module->getPath($fullPath)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module argument
        $module = $input->getArgument('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setArgument('module', $module);
        }
    }
}
