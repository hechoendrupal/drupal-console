<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Chain\ChainDebugCommand.
 */

namespace Drupal\Console\Command\Chain;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Command;
use Symfony\Component\Finder\Finder;

/**
 * Class ChainDebugCommand
 * @package Drupal\Console\Command\Chain
 */
class ChainDebugCommand extends Command
{
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
        $config = $this->getApplication()->getConfig();

        $directories = [
          $config->getUserHomeDir() . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain'
        ];


        foreach ($directories as $directory) {
            $io->info($this->trans('commands.chain.debug.messages.directory'), false);
            $io->comment($directory);

            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in($directory);

            $tableHeader = [
              $this->trans('commands.chain.debug.messages.file')
            ];

            $tableRows = [];
            foreach ($finder as $chain) {
                $tableRows[] = $chain->getBasename();
            }

            $io->table($tableHeader, $tableRows);
        }
    }
}
