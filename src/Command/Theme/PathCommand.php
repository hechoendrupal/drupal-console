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
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Extension\ThemeHandler;

class PathCommand extends Command
{
    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * PathCommand constructor.
     *
     * @param Manager      $extensionManager
     * @param ThemeHandler $themeHandler
     */
    public function __construct(Manager $extensionManager, ThemeHandler $themeHandler)
    {
        $this->extensionManager = $extensionManager;
        $this->themeHandler = $themeHandler;
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
                $this->trans('commands.theme.path.arguments.theme')
            )
            ->addOption(
                'absolute',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.theme.path.options.absolute')
            )->setAliases(['thp']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $theme = $input->getArgument('theme');

        $fullPath = $input->getOption('absolute');

        if (!in_array($theme, $this->getThemeList())) {
            $io->error(
                sprintf(
                    $this->trans('commands.theme.path.messages.invalid-theme-name'),
                    $theme
                )
            );
            return;
        }
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

        // --theme argument
        $theme = $input->getArgument('theme');
        if (!$theme) {
            $theme = $io->choiceNoList(
                $this->trans('commands.theme.path.arguments.theme'),
                $this->getThemeList()
            );
            $input->setArgument('theme', $theme);
        }
    }

    protected function getThemeList()
    {
        return array_keys($this->themeHandler->rebuildThemeData());
    }
}
