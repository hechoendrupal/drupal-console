<?php

/**
 * @file
 * Contains \Drupal\Console\Command\AboutCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

class AboutCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription($this->trans('commands.about.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $application = $this->getApplication();

        $aboutTitle = sprintf(
            '%s (%s)',
            $this->trans('commands.site.status.messages.console'),
            $application->getVersion()
        );

        $output->setDecorated(false);
        $output->title($aboutTitle);
        $output->setDecorated(true);

        $output->writeln(
            $this->trans('commands.about.messages.welcome')
        );

        $features = [
          $this->trans('commands.about.messages.welcome-feature-learn'),
          $this->trans('commands.about.messages.welcome-feature-generate'),
          $this->trans('commands.about.messages.welcome-feature-interact')
        ];

        $output->listing(
            array_map(
                function ($element) {
                    return sprintf('<comment>%s</comment>', $element);
                },
                $features
            )
        );

        $commands = [
            'move-phar' => [
                $this->trans('commands.common.messages.move-phar'),
                'mv console.phar /usr/local/bin/drupal'
            ],
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
            'links' => [
                $this->trans('commands.list.description'),
                'drupal list',
            ]
        ];

        foreach ($commands as $command => $commandInfo) {
            $output->writeln($commandInfo[0]);
            $output->newLine();
            $output->writeln(sprintf('  <comment>%s</comment>', $commandInfo[1]));
            $output->newLine();
        }

        $output->setDecorated(false);
        $output->section($this->trans('commands.self-update.description'));
        $output->setDecorated(true);
        $output->writeln('  <comment>drupal self-update</comment>');
        $output->newLine();
    }
}
