<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\DebugCommand.
 */

namespace Drupal\Console\Command\Site;

use Drupal\Console\Command\Command;
use Drupal\Console\Console\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;

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
            ->setName('site:debug')
            ->setDescription($this->trans('commands.site.debug.description'))
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                $this->trans('commands.site.debug.options.target'),
                null
            )
            ->setHelp($this->trans('commands.site.debug.help'));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getMessageHelper();
        $application = $this->getApplication();
        $sitesDirectory = $application->getConfig()->getSitesDirectory();

        if (!is_dir($sitesDirectory)) {
            $message->addErrorMessage(
                sprintf(
                    $this->trans('commands.site.debug.messages.directory-not-found'),
                    $sitesDirectory
                )
            );
            return;
        }

        // Get the target argument
        $target = $input->getArgument('target');
        if ($target && $application->getConfig()->loadTarget($target)) {
            $targetConfig = $application->getConfig()->getTarget($target);
            $dumper = new Dumper();
            $yaml = $dumper->dump($targetConfig, 5);
            $output->writeln($yaml);
            return;
        }


        $finder = new Finder();
        $finder->in($sitesDirectory);
        $finder->name("*.yml");

        $table = new Table($output);

        $table->setHeaders(
            [
                $this->trans('commands.site.debug.messages.site'),
                $this->trans('commands.site.debug.messages.host'),
                $this->trans('commands.site.debug.messages.root')
            ]
        );

        foreach ($finder as $site) {
            $siteConfiguration = $site->getBasename('.yml');
            $application->getConfig()->loadSite($siteConfiguration);
            $environments = $application->getConfig()->get('sites.'.$siteConfiguration);
            foreach ($environments as $env => $config) {
                $table->addRow(
                    [
                      $siteConfiguration . '.' . $env,
                      array_key_exists('host', $config) ? $config['host'] : 'local',
                      array_key_exists('root', $config) ? $config['root'] : ''
                    ]
                );
            }
        }
        $table->render();
    }
}
