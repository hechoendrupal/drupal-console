<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Chain\ChainDebugCommand.
 */

namespace Drupal\Console\Command\Chain;

use Drupal\Console\Command\ChainFilesTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Command;

/**
 * Class ChainDebugCommand
 * @package Drupal\Console\Command\Chain
 */
class ChainDebugCommand extends Command
{
    use ChainFilesTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('chain:debug')
            ->setDescription($this->trans('commands.chain.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $files = $this->getChainFiles();

        foreach ($files as $directory => $chainFiles) {
            $io->info($this->trans('commands.chain.debug.messages.directory'), false);
            $io->comment($directory);

            $tableHeader = [
              $this->trans('commands.chain.debug.messages.file')
            ];

            $tableRows = [];
            foreach ($chainFiles as $file) {
                $tableRows[] = $file;
            }

            $io->table($tableHeader, $tableRows);
        }
    }
}
