<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparerInterface;

class ImportCommand extends Command
{
    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * ImportCommand constructor.
     *
     * @param CachedStorage $configStorage
     * @param ConfigManager $configManager
     */
    public function __construct(
        CachedStorage $configStorage,
        ConfigManager $configManager
    ) {
        $this->configStorage = $configStorage;
        $this->configManager = $configManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:import')
            ->setDescription($this->trans('commands.config.import.description'))
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.import.options.file')
            )
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.import.options.directory')
            )
            ->addOption(
                'remove-files',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.import.options.remove-files')
            )
            ->addOption(
                'skip-uuid',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.import.options.skip-uuid')
            )
            ->setAliases(['ci']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('directory')) {
            $directory = $this->getIo()->ask(
                $this->trans('commands.config.import.questions.directory'),
                Settings::get('config_sync_directory')
        );
            $input->setOption('directory', $directory);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getOption('directory');
        $skipUuid = $input->getOption('skip-uuid');

        if ($directory) {
            $source_storage = new FileStorage($directory);
        } else {
            $source_storage = \Drupal::service('config.storage.sync');
        }

        $storageComparer = '\Drupal\Core\Config\StorageComparer';
        if ($skipUuid) {
            $storageComparer = '\Drupal\Console\Override\StorageComparer';
        }

        $storage_comparer = new $storageComparer(
            $source_storage,
            $this->configStorage,
            $this->configManager
        );

        if (!$storage_comparer->createChangelist()->hasChanges()) {
            $this->getIo()->success($this->trans('commands.config.import.messages.nothing-to-do'));
        }

        if ($this->configImport($storage_comparer)) {
            $this->getIo()->success($this->trans('commands.config.import.messages.imported'));
        } else {
            return 1;
        }
    }


    private function configImport(StorageComparerInterface $storage_comparer)
    {
        $config_importer = new ConfigImporter(
            $storage_comparer,
            \Drupal::service('event_dispatcher'),
            \Drupal::service('config.manager'),
            \Drupal::lock(),
            \Drupal::service('config.typed'),
            \Drupal::moduleHandler(),
            \Drupal::service('module_installer'),
            \Drupal::service('theme_handler'),
            \Drupal::service('string_translation'),
            \Drupal::service('extension.list.module')
        );

        if ($config_importer->alreadyImporting()) {
            $this->getIo()->success($this->trans('commands.config.import.messages.already-imported'));
        } else {
            try {
                $this->getIo()->info($this->trans('commands.config.import.messages.importing'));
                $config_importer->import();
                return true;
            } catch (ConfigImporterException $e) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.site.import.local.messages.error-writing'),
                        implode("\n", $config_importer->getErrors())
                    )
                );
            } catch (\Exception $e) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.site.import.local.messages.error-writing'),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
