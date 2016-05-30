<?php

/**
 * @file
 * Contains \Drupal\Console\Command\AboutCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class AboutCommand extends BaseCommand
{
    use CommandTrait;

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

        $site = $this->get('site');
        $drupalVersion = $this->trans('commands.site.status.messages.not_installed');
        if ($site->isInstalled()) {
            $drupalVersion = sprintf(
                $this->trans('commands.site.status.messages.current_version'),
                $site->getDrupalVersion()
            );
        }

        $aboutTitle = sprintf(
            '%s (%s) | %s',
            $this->trans('commands.site.status.messages.console'),
            $application->getVersion(),
            $drupalVersion
        );

        $io->setDecorated(false);
        $io->title($aboutTitle);
        $io->setDecorated(true);

        $commands = [
            'init' => [
                $this->trans('commands.init.description'),
                'drupal init --override'
            ],
            'quick-start' => [
                $this->trans('commands.common.messages.quick-start'),
                'drupal chain --file=~/.console/chain/quick-start.yml'
            ],
            'site-new' => [
                $this->trans('commands.site.new.description'),
                'drupal site:new drupal8.dev --latest'
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
