<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\DebugCommand.
 */

namespace Drupal\Console\Command\Multisite;

use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SiteDebugCommand
 * @package Drupal\Console\Command\Site
 */
class DebugCommand extends Command
{
    /**
     * @{@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('multisite:debug')
            ->setDescription($this->trans('commands.multisite.debug.description'))
            ->setHelp($this->trans('commands.multisite.debug.help'));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $sites = [];

        $multiSiteFile = sprintf(
            '%s/sites/sites.php',
            $this->getDrupalHelper()->getRoot()
        );

        if (file_exists($multiSiteFile)) {
            include $multiSiteFile;
        }

        if (!$sites) {
            $io->error(
                $this->trans('commands.multisite.debug.messages.no-multisites')
            );

            return;
        }

        $io->info(
            $this->trans('commands.multisite.debug.messages.site-format')
        );

        $tableHeader = [
            $this->trans('commands.multisite.debug.messages.site'),
            $this->trans('commands.multisite.debug.messages.directory'),
        ];

        $tableRows = [];
        foreach ($sites as $site => $directory) {
            $tableRows[] = [
                $site,
                $this->getDrupalHelper()->getRoot()  . '/' . $directory
            ];
        }

        $io->table($tableHeader, $tableRows);
    }
}
