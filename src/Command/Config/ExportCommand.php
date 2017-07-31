<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Config\ConfigManager;

class ExportCommand extends Command
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * ExportCommand constructor.
     *
     * @param ConfigManagerInterface $configManager
     * @param StorageInterface       $storage
     */
    public function __construct(ConfigManagerInterface $configManager, StorageInterface $storage)
    {
        parent::__construct();
        $this->configManager = $configManager;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:export')
            ->setDescription($this->trans('commands.config.export.description'))
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.options.directory')
            )
            ->addOption(
                'tar',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.options.tar')
            )->addOption(
                'remove-uuid',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.options.remove-uuid')
            )->addOption(
                'remove-config-hash',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.options.remove-config-hash')
            )
            ->setAliases(['ce']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getOption('directory');
        $tar = $input->getOption('tar');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');

        if (!$directory) {
            $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        $fileSystem = new Filesystem();
        try {
            $fileSystem->mkdir($directory);
        } catch (IOExceptionInterface $e) {
            $io->error(
                sprintf(
                    $this->trans('commands.config.export.messages.error'),
                    $e->getPath()
                )
            );
        }

        // Remove previous yaml files before creating new ones
        array_map('unlink', glob($directory . '/*'));

        if ($tar) {
            $dateTime = new \DateTime();

            $archiveFile = sprintf(
                '%s/config-%s.tar.gz',
                $directory,
                $dateTime->format('Y-m-d-H-i-s')
            );
            $archiveTar = new ArchiveTar($archiveFile, 'gz');
        }

        try {
            // Get raw configuration data without overrides.
            foreach ($this->configManager->getConfigFactory()->listAll() as $name) {
                $configName = "$name.yml";
                $configData = $this->configManager->getConfigFactory()->get($name)->getRawData();
                if ($removeUuid) {
                    unset($configData['uuid']);
                }
                if ($removeHash) {
                    unset($configData['_core']['default_config_hash']);
                }
                $ymlData = Yaml::encode($configData);

                if ($tar) {
                    $archiveTar->addString($configName, $ymlData);
                } else {
                    file_put_contents("$directory/$configName", $ymlData);
                }
            }
            // Get all override data from the remaining collections.
            foreach ($this->storage->getAllCollectionNames() as $collection) {
                $collection_storage = $this->storage->createCollection($collection);
                foreach ($collection_storage->listAll() as $name) {
                    $configName = str_replace('.', '/', $collection) . "/$name.yml";
                    $configData = $collection_storage->read($name);
                    if ($removeUuid) {
                        unset($configData['uuid']);
                    }
                    if ($removeHash) {
                        unset($configData['_core']['default_config_hash']);
                    }

                    $ymlData = Yaml::encode($configData);
                    if ($tar) {
                        $archiveTar->addString($configName, $ymlData);
                    } else {
                        file_put_contents("$directory/$configName", $ymlData);
                    }
                }
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        $io->info(
            sprintf(
                $this->trans('commands.config.export.messages.directory'),
                $directory
            )
        );
    }
}
