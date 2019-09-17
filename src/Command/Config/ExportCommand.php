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
            )->addOption(
                'tar',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.options.tar')
            )
            ->setAliases(['ce']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('directory')) {
            $directory = $this->getIo()->ask(
                $this->trans('commands.config.export.questions.directory'),
                config_get_config_directory(CONFIG_SYNC_DIRECTORY)
            );
            $input->setOption('directory', $directory);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $drupal_root = $this->drupalFinder->getComposerRoot();
        $directory = $drupal_root.'/'.$input->getOption('directory');
        $tar = $input->getOption('tar');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');
        $drupal_root = $this->drupalFinder->getComposerRoot();

        if (!$directory) {
            $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        $fileSystem = new Filesystem();
        try {
            $fileSystem->mkdir($drupal_root."/".$directory);
        } catch (IOExceptionInterface $e) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.config.export.messages.error'),
                    $e->getPath()
                )
            );
        }

        // Remove previous yaml files before creating new ones
        foreach (glob($directory . '/*') as $item) {
            $fileSystem->remove($item);
        }

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
                    if (empty($configData['_core'])) {
                        unset($configData['_core']);
                    }
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
                $collection_path = str_replace('.', '/', $collection);
                if (!$tar) {
                    $fileSystem->mkdir("$directory/$collection_path", 0755);
                }
                foreach ($collection_storage->listAll() as $name) {
                    $configName = "$collection_path/$name.yml";
                    $configData = $collection_storage->read($name);
                    if ($removeUuid) {
                        unset($configData['uuid']);
                    }
                    if ($removeHash) {
                        unset($configData['_core']['default_config_hash']);
                        if (empty($configData['_core'])) {
                            unset($configData['_core']);
                        }
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
            $this->getIo()->error($e->getMessage());
        }

        $this->getIo()->info(
            sprintf(
                $this->trans('commands.config.export.messages.directory'),
                $directory
            )
        );
    }
}
