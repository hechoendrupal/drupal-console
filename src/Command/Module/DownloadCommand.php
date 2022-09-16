<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DownloadCommand.
 */

namespace Drupal\Console\Command\Module;

use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ProjectDownloadTrait;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Utils\Site;

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
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Site
     */
    protected $site;

    /**
     * DownloadCommand constructor.
     *
     * @param DrupalApi            $drupalApi
     * @param Client               $httpClient
     * @param $appRoot
     * @param Manager              $extensionManager
     * @param Validator            $validator
     * @param Site                 $site
     */
    public function __construct(
        DrupalApi $drupalApi,
        Client $httpClient,
        $appRoot,
        Manager $extensionManager,
        Validator $validator,
        Site $site
    ) {
        $this->drupalApi = $drupalApi;
        $this->httpClient = $httpClient;
        $this->appRoot = $appRoot;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->site = $site;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('module:download')
            ->setDescription($this->trans('commands.module.download.description'))
            ->addArgument(
                'module',
                InputArgument::IS_ARRAY,
                $this->trans('commands.module.download.arguments.module')
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.module.download.options.path')
            )
            ->addOption(
                'latest',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.module.download.options.latest')
            )
            ->setAliases(['mod']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');

        if (!$module) {
            $module = $this->modulesQuestion();
            $input->setArgument('module', $module);
        }

        $path = $input->getOption('path');
        if (!$path) {
            $path = $this->getIo()->ask(
                $this->trans('commands.module.download.questions.path'),
                'modules/contrib'
            );
            $input->setOption('path', $path);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $input->getArgument('module');
        $latest = $input->getOption('latest');
        $path = $input->getOption('path');

        $this->downloadModules($modules, $latest, $path);

        return 1;
    }
}
