<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ConfigCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Console\Core\Command\Command;

class ConfigCommand extends Command
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * ConfigCommand constructor.
     *
     * @param ConfigFactory $configFactory
     * @param CachedStorage $configStorage
     */
    public function __construct(
        ConfigFactory $configFactory,
        CachedStorage $configStorage
    ) {
        $this->configFactory = $configFactory;
        $this->configStorage = $configStorage;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:config')
            ->setDescription($this->trans('commands.debug.config.description'))
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.config.arguments.name')
            )
            ->addOption(
                'show-overridden',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.debug.config.options.show-overridden')
            )
            ->setAliases(['dc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('name');
        $showOverridden = $input->getOption('show-overridden');

        if (!$configName) {
            $this->getAllConfigurations();
        } else {
            $this->getConfigurationByName($configName, $showOverridden);
        }
    }

    private function getAllConfigurations()
    {
        $names = $this->configFactory->listAll();
        $tableHeader = [
            $this->trans('commands.debug.config.arguments.name'),
        ];
        $tableRows = [];
        foreach ($names as $name) {
            $tableRows[] = [
                $name,
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }

    /**
     * @param $config_name     String
     * @param $showOverridden  bool
     */
    private function getConfigurationByName($config_name, $showOverridden = false)
    {
        if ($this->configStorage->exists($config_name)) {
            $tableHeader = [
                $config_name,
            ];
            $configuration = $this->configStorage->read($config_name);
            if ($showOverridden) {
                $configurationKeys = array_keys($configuration);
                foreach ($configurationKeys as $configurationKey) {
                    $configuration[$configurationKey] = $this->configFactory
                        ->get($config_name)
                        ->get($configurationKey);
                }
            }

            $configurationEncoded = Yaml::encode($configuration);
            $tableRows = [];
            $tableRows[] = [
                $configurationEncoded,
            ];

            $this->getIo()->table($tableHeader, $tableRows, 'compact');
        } else {
            $this->getIo()->error(
                sprintf($this->trans('commands.debug.config.errors.not-exists'), $config_name)
            );
        }
    }
}
