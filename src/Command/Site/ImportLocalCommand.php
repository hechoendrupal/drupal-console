<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\ImportLocalCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ImportLocalCommand
 *
 * @package Drupal\Console\Command\Site
 */
class ImportLocalCommand extends Command
{
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * ImportLocalCommand constructor.
     *
     * @param $appRoot
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        $appRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->appRoot = $appRoot;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('site:import:local')
            ->setDescription($this->trans('commands.site.import.local.description'))
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                $this->trans('commands.site.import.local.arguments.name')
            )
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                $this->trans('commands.site.import.local.arguments.directory')
            )
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.import.local.options.environment')
            )
            ->setHelp($this->trans('commands.site.import.local.help'))
            ->setAliases(['sil']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteName = $input->getArgument('name');
        $directory = $input->getArgument('directory');

        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($directory)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.site.import.local.messages.error-missing'),
                    $directory
                )
            );

            return 1;
        }
        
        $environment = $input->getOption('environment')?:'local';

        $siteConfig = [
          $environment => [
            'root' => $this->appRoot,
            'host' => 'local',
          ],
        ];

        $yaml = new Yaml();
        $dump = $yaml::dump($siteConfig);

        $userPath = sprintf('%s/.console/sites', $this->configurationManager->getHomeDirectory());
        $configFile = sprintf('%s/%s.yml', $userPath, $siteName);

        try {
            $fileSystem->dumpFile($configFile, $dump);
        } catch (\Exception $e) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.site.import.local.messages.error-writing'),
                    $e->getMessage()
                )
            );

            return 1;
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.site.import.local.messages.imported')
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('directory');
        if (!$directory) {
            $directory = $this->getIo()->ask(
                $this->trans('commands.site.import.local.questions.directory'),
                getcwd()
            );
            $input->setArgument('directory', $directory);
        }

        $name = $input->getArgument('name');
        if (!$name) {
            $name = $this->getIo()->ask(
                $this->trans('commands.site.import.local.questions.name')
            );
            $input->setArgument('name', $name);
        }
    }
}
