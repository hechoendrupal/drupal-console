<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\PathCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Style\DrupalStyle;

class PathCommand extends Command
{
    use CommandTrait;
    use ModuleTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * PathCommand constructor.
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
            ->setName('theme:path')
            ->setDescription($this->trans('commands.theme.path.description'))
            ->addArgument(
                'theme',
                InputArgument::REQUIRED,
                $this->trans('commands.theme.path.arguments.module')
            )
            ->addOption(
                'absolute',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.theme.path.options.absolute')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $theme = $input->getArgument('theme');

        $fullPath = $input->getOption('absolute');

        $theme = $this->extensionManager->getTheme($theme);

        $io->info(
            $theme->getPath($fullPath)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module argument
        $theme = $input->getArgument('theme');
        if (!$theme) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setArgument('theme', $module);
        }
    }
}
