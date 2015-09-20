<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\views\Entity\View;
use Drupal\Component\Serialization\Yaml;

class AboutCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription($this->trans('commands.about.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            $this->trans('commands.about.messages.welcome')
        );

        $output->writeln("    <comment>" . $this->trans('commands.about.messages.welcome-feature-learn') . "</comment>");
        $output->writeln("    <comment>" . $this->trans('commands.about.messages.welcome-feature-generate') . "</comment>");
        $output->writeln("    <comment>" . $this->trans('commands.about.messages.welcome-feature-interact') . "</comment>");
        $output->writeln("");

        $output->writeln(
            sprintf(
                $this->trans('commands.about.messages.version-supported'),
                'Drupal 8 Beta 15'
            )
        );

        $output->writeln("");

        $output->writeln(
            $this->trans('commands.about.messages.list')
        );

        $output->writeln("");

        $output->writeln(
            sprintf(
                $this->trans('commands.about.messages.change-log'),
                'http://bit.ly/console-releases'
            )
        );


        $output->writeln(
            sprintf(
                $this->trans('commands.about.messages.documentation'),
                'http://bit.ly/console-book'
            )
        );

        $output->writeln(
            sprintf(
                $this->trans('commands.about.messages.support'),
                'http://bit.ly/console-support'
            )
        );

        $output->writeln("");

        $output->writeln("<info>" . $this->trans('commands.about.messages.supporting-organizations') . "</info>");

        $output->writeln("    <comment>" . 'Indava (http://www.indava.com/)' . "</comment>");
        $output->writeln("    <comment>" . 'Anexus (https://anexusit.com)' . "</comment>");
        $output->writeln("    <comment>" . 'FFW (https://ffwagency.com)' . "</comment>");
    }
}
