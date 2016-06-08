<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\DownloadCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('theme:download')
            ->setDescription($this->trans('commands.theme.download.description'))
            ->addArgument('theme', InputArgument::REQUIRED, $this->trans('commands.theme.download.options.theme'))
            ->addArgument('version', InputArgument::OPTIONAL, $this->trans('commands.theme.download.options.version'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $theme = $input->getArgument('theme');
        $version = $input->getArgument('version');

        $this->downloadProject($io, $theme, $version, 'theme');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $theme = $input->getArgument('theme');
        $version = $input->getArgument('version');

        if (!$version) {
            $version = $this->releasesQuestion($io, $theme);
            $input->setArgument('version', $version);
        }
    }
}
