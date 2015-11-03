<?php

/**
 * @file
 * Contains \Drupal\Console\Command\AboutCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $renderer = $this->getRenderHelper();
        $application = $this->getApplication();

        $features = [
          $this->trans('commands.about.messages.welcome-feature-learn'),
          $this->trans('commands.about.messages.welcome-feature-generate'),
          $this->trans('commands.about.messages.welcome-feature-interact')
        ];

        $consoleVersion = sprintf(
            '%s <info>%s</info>',
            $this->trans('commands.site.status.messages.console'),
            $application->getVersion()
        );

        $supportedVersion = sprintf(
            $this->trans('commands.about.messages.version-supported'),
            $application::DRUPAL_VERSION
        );

        $links = [
          sprintf(
              $this->trans('commands.about.messages.landing'),
              'http://drupalconsole.com'
          ),
          sprintf(
              $this->trans('commands.about.messages.change-log'),
              'http://bit.ly/console-releases'
          ),
          sprintf(
              $this->trans('commands.about.messages.documentation'),
              'http://bit.ly/console-book'
          ),
          sprintf(
              $this->trans('commands.about.messages.support'),
              'http://bit.ly/console-support'
          )
        ];

        $organizations = [
            'Indava (http://www.indava.com/)',
            'Anexus (https://anexusit.com)',
            'FFW (https://ffwagency.com)'
        ];

        $parameters = [
          'title' =>  $this->trans('commands.about.messages.welcome'),
          'features' => $features,
          'console_version' => $consoleVersion,
          'supported_version' => $supportedVersion,
          'list_command' => $this->trans('commands.about.messages.list'),
          'links' => $links,
          'supporting_organizations' => $this->trans('commands.about.messages.supporting-organizations'),
          'organizations' => $organizations
        ];

        $about = $renderer->render('core/about.twig', $parameters);

        $output->writeln($about);
    }
}
