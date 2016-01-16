<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DownloadCommand.
 */

namespace Drupal\Console\Command\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\ProjectDownloadTrait;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;

    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription($this->trans('commands.module.download.description'))
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                $this->trans('commands.module.download.options.module')
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                $this->trans('commands.module.download.options.version'),
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        $version = $input->getArgument('version');

        $this->downloadProject($io, $module, $version, 'module');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $module = $input->getArgument('module');
        $version = $input->getArgument('version');

        if (!$version) {
            $version = $this->releasesQuestion($io, $module);
            $input->setArgument('version', $version);
        }
    }
}
