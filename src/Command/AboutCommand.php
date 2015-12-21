<?php

/**
 * @file
 * Contains \Drupal\Console\Command\AboutCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class AboutCommand
 * @package Drupal\Console\Command
 */
class AboutCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription($this->trans('commands.about.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $application = $this->getApplication();

        $aboutTitle = sprintf(
            '%s (%s) | Supports Drupal %s',
            $this->trans('commands.site.status.messages.console'),
            $application->getVersion(),
            $application::DRUPAL_VERSION
        );

        $io->setDecorated(false);
        $io->title($aboutTitle);
        $io->setDecorated(true);

        $commands = [
            'init' => [
                $this->trans('commands.init.description'),
                'drupal init [--override]'
            ],
            'quick-start' => [
                $this->trans('commands.common.messages.quick-start'),
                'drupal chain --file=~/.console/chain/quick-start.yml'
            ],
            'site-new' => [
                $this->trans('commands.site.new.description'),
                sprintf(
                    'drupal site:new drupal8.dev %s',
                    $application::DRUPAL_VERSION
                )
            ],
            'site-install' => [
            $this->trans('commands.site.install.description'),
            sprintf(
                'drupal site:install'
            )
            ],
            'links' => [
                $this->trans('commands.list.description'),
                'drupal list',
            ]
        ];

        foreach ($commands as $command => $commandInfo) {
            $io->writeln($commandInfo[0]);
            $io->newLine();
            $io->comment(sprintf('  %s', $commandInfo[1]));
            $io->newLine();
        }

        $io->setDecorated(false);
        $io->section($this->trans('commands.self-update.description'));
        $io->setDecorated(true);
        $io->comment('  drupal self-update');
        $io->newLine();
    }
}
