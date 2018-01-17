<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\MultisiteCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;

/**
 * Class MultisiteCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class MultisiteCommand extends Command
{
    protected $appRoot;

    /**
     * MultisiteCommand constructor.
     *
     * @param $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('debug:multisite')
            ->setDescription($this->trans('commands.debug.multisite.description'))
            ->setHelp($this->trans('commands.debug.multisite.help'))
            ->setAliases(['dmu']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sites = [];

        $multiSiteFile = sprintf(
            '%s/sites/sites.php',
            $this->appRoot
        );

        if (file_exists($multiSiteFile)) {
            include $multiSiteFile;
        }

        if (!$sites) {
            $this->getIo()->error(
                $this->trans('commands.debug.multisite.messages.no-multisites')
            );

            return 1;
        }

        $this->getIo()->info(
            $this->trans('commands.debug.multisite.messages.site-format')
        );

        $tableHeader = [
            $this->trans('commands.debug.multisite.messages.site'),
            $this->trans('commands.debug.multisite.messages.directory'),
        ];

        $tableRows = [];
        foreach ($sites as $site => $directory) {
            $tableRows[] = [
                $site,
                $this->appRoot  . '/sites/' . $directory
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows);

        return 0;
    }
}
