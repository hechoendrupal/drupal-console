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
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\Site;

/**
 * Class SiteDebugCommand
 * @package Drupal\Console\Command\Site
 */
class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * DebugCommand constructor.
     * @param Site                 $site
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        Site $site,
        ConfigurationManager $configurationManager
    ) {
        $this->site = $site;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

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
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $sitesDirectory =  $this->configurationManager->getSitesDirectory();

        if (!is_dir($sitesDirectory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.debug.messages.directory-not-found'),
                    $sitesDirectory
                )
            );

            return 1;
        }

        // --target argument
        $target = $input->getArgument('target');
        if ($target) {
            $io->write(
                $this->siteDetail($target)
            );

            return 0;
        }

        $tableHeader =[
            $this->trans('commands.site.debug.messages.site'),
            $this->trans('commands.site.debug.messages.host'),
            $this->trans('commands.site.debug.messages.root')
        ];

        $tableRows = $this->siteList($sitesDirectory);

        $io->table($tableHeader, $tableRows);
        return 0;
    }

    /**
     * @param string $target
     *
     * @return string
     */
    private function siteDetail($target)
    {
        if ($targetConfig = $this->configurationManager->readTarget($target)) {
            $dumper = new Dumper();

            return $dumper->dump($targetConfig, 2);
        }
    }

    /**
     * @param DrupalStyle $io
     * @param string      $sitesDirectory
     * @return array
     */
    private function siteList($sitesDirectory)
    {
        $finder = new Finder();
        $finder->in($sitesDirectory);
        $finder->name("*.yml");

        $tableRows = [];
        foreach ($finder as $site) {
            $siteName = $site->getBasename('.yml');
            $environments = $this->configurationManager
                ->readSite($site->getRealPath());

            foreach ($environments as $env => $config) {
                $tableRows[] = [
                    $siteName . '.' . $env,
                  array_key_exists('host', $config) ? $config['host'] : 'local',
                  array_key_exists('root', $config) ? $config['root'] : ''
                ];
            }
        }

        return $tableRows;
    }
}
