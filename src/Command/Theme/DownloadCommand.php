<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\DownloadCommand.
 */

namespace Drupal\Console\Command\Theme;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Utils\DrupalApi;
use GuzzleHttp\Client;

class DownloadCommand extends Command
{
    use ProjectDownloadTrait;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $appRoot;


    /**
     * DownloadCommand constructor.
     *
     * @param DrupalApi $drupalApi
     * @param Client    $httpClient
     * @param $appRoot
     */
    public function __construct(
        DrupalApi $drupalApi,
        Client $httpClient,
        $appRoot
    ) {
        $this->drupalApi = $drupalApi;
        $this->httpClient = $httpClient;
        $this->appRoot = $appRoot;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('theme:download')
            ->setDescription($this->trans('commands.theme.download.description'))
            ->addArgument(
                'theme',
                InputArgument::REQUIRED,
                $this->trans('commands.theme.download.arguments.theme')
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                $this->trans('commands.theme.download.arguments.version')
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.theme.download.options.composer')
            )->setAliases(['thd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');
        $version = $input->getArgument('version');
        $composer = $input->getOption('composer');

        if ($composer) {
            if (!is_array($theme)) {
                $theme = [$theme];
            }
            $this->get('chain_queue')->addCommand(
                'module:download',
                [
                'module' => $theme,
                '--composer' => true
                ],
                true,
                true
            );
        } else {
            $this->downloadProject($theme, $version, 'theme');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');
        $version = $input->getArgument('version');
        $composer = $input->getOption('composer');

        if (!$version && !$composer) {
            $version = $this->releasesQuestion($theme);
            $input->setArgument('version', $version);
        }
    }
}
