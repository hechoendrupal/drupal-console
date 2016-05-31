<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\DebugCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class SiteDebugCommand
 * @package Drupal\Console\Command\Site
 */
class DebugCommand extends Command
{
    use CommandTrait;

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
        $io = new DrupalStyle($input, $output);

        $application = $this->getApplication();
        $sitesDirectory = $application->getConfig()->getSitesDirectory();

        if (!is_dir($sitesDirectory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.debug.messages.directory-not-found'),
                    $sitesDirectory
                )
            );

            return;
        }

        // --target argument
        $target = $input->getArgument('target');
        if ($target) {
            $this->siteDetail($io, $target);

            return;
        }

        $this->siteList($io, $sitesDirectory);
    }

    /**
     * @param string $target
     */
    private function siteDetail(DrupalStyle $io, $target)
    {
        $application = $this->getApplication();
        if ($application->getConfig()->loadTarget($target)) {
            $targetConfig = $application->getConfig()->getTarget($target);
            $dumper = new Dumper();
            $yaml = $dumper->dump($targetConfig, 5);
            $io->writeln($yaml);

            return;
        }
    }

    /**
     * @param DrupalStyle $io
     * @param string      $sitesDirectory
     */
    private function siteList(DrupalStyle $io, $sitesDirectory)
    {
        $application = $this->getApplication();

        $finder = new Finder();
        $finder->in($sitesDirectory);
        $finder->name("*.yml");

        $tableHeader =[
            $this->trans('commands.site.debug.messages.site'),
            $this->trans('commands.site.debug.messages.host'),
            $this->trans('commands.site.debug.messages.root')
        ];

        $tableRows = [];
        foreach ($finder as $site) {
            $siteConfiguration = $site->getBasename('.yml');
            $application->getConfig()->loadSite($siteConfiguration);
            $environments = $application->getConfig()->get('sites.'.$siteConfiguration);
            foreach ($environments as $env => $config) {
                $tableRows[] = [
                  $siteConfiguration . '.' . $env,
                  array_key_exists('host', $config) ? $config['host'] : 'local',
                  array_key_exists('root', $config) ? $config['root'] : ''
                ];
            }
        }

        $io->table($tableHeader, $tableRows);
    }
}
