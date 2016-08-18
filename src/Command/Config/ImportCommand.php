<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;

class ImportCommand extends Command
{
    use ContainerAwareCommandTrait;
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
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.import.options.remove-files')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $directory = $input->getOption('directory');

        if ($directory) {
            $configSyncDir = $directory;
        } else {
            $configSyncDir = config_get_config_directory(
                CONFIG_SYNC_DIRECTORY
            );
        }

        // Determine $source_storage in partial and non-partial cases.
        $active_storage = \Drupal::service('config.storage');

        $source_storage = new FileStorage($configSyncDir);

        /**
 * @var \Drupal\Core\Config\ConfigManagerInterface $config_manager 
*/
        $config_manager = \Drupal::service('config.manager');
        $storage_comparer = new StorageComparer($source_storage, $active_storage, $config_manager);

        if (!$storage_comparer->createChangelist()->hasChanges()) {
            $io->success($this->trans('commands.config.import.messages.nothing-to-do'));
        }

        if ($this->configImport($io, $storage_comparer)) {
            $io->success($this->trans('commands.config.import.messages.imported'));
        }
    }


    private function configImport(DrupalStyle $io, StorageComparer $storage_comparer)
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
            \Drupal::service('string_translation')
        );

        if ($config_importer->alreadyImporting()) {
            $io->success($this->trans('commands.config.import.messages.already-imported'));
        } else {
            try {
                $config_importer->import();
                $io->info($this->trans('commands.config.import.messages.importing'));
            } catch (ConfigImporterException $e) {
                $message = 'The import failed due for the following reasons:' . "\n";
                $message .= implode("\n", $config_importer->getErrors());
                $io->error(
                    sprintf(
                        $this->trans('commands.site.import.local.messages.error-writing'),
                        $message
                    )
                );
            } catch (\Exception $e) {
                $io->error(
                    sprintf(
                        $this->trans('commands.site.import.local.messages.error-writing'),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
