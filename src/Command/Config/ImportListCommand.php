<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportListCommand.
 */
namespace Drupal\Console\Command\Config;

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\StorageComparer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Drupal\Component\Serialization\Yaml;

class ImportListCommand extends Command
{
    use CommandTrait;

    /** @var CachedStorage */
    protected $configStorage;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * ImportSingleCommand constructor.
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
          ->setName('config:import:list')
          ->setDescription(
            $this->trans('commands.config.import.list.description')
          )
          ->addArgument(
            'config-list-file',
            InputArgument::REQUIRED,
            $this->trans(
              'commands.config.import.list.arguments.config-list-file'
            )
          )
          ->addOption(
            'directory',
            '',
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.config.import.list.options.directory')
          );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configListFile = $input->getArgument('config-list-file');
        $directory = $input->getOption('directory');

        if (!$directory) {
            $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }
        else {
            $directory = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $directory;
        }

        $configNames = $this->parseYMLConfigListFile($configListFile);

        foreach ($configNames as $configName) {
            // Allow for accidental .yml extension.
            if (substr($configName, -4) === '.yml') {
                $configName = substr($configName, 0, -4);
            }

            $fileName = $directory . DIRECTORY_SEPARATOR . $configName . '.yml';
            $ymlFile = new Parser();

            if (!empty($fileName) && file_exists($fileName)) {
                $value = $ymlFile->parse(file_get_contents($fileName));
            } else {
                $value = $ymlFile->parse(
                  stream_get_contents(fopen("php://stdin", "r"))
                );
            }


            if (empty($value)) {
                $io->error(
                  $this->trans(
                    'commands.config.import.list.messages.empty-value'
                  )
                );

                return;
            }

            try {
                $source_storage = new StorageReplaceDataWrapper(
                  $this->configStorage
                );
                $source_storage->replaceData($configName, $value);
                $storage_comparer = new StorageComparer(
                  $source_storage,
                  $this->configStorage,
                  $this->configManager
                );

                if ($this->configImport($io, $storage_comparer)) {
                    $io->success(
                      sprintf(
                        $this->trans(
                          'commands.config.import.list.messages.success'
                        ),
                        $configName
                      )
                    );
                }
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return 1;
            }
        }
    }

    /**
     * Parse the config list file (YML format)
     *
     * @param $config_list File containing the list of configs to import
     * @return boolean whether the parsing did work
     */
    private function parseYMLConfigListFile($config_list_file) {
        if ($string = file_get_contents(DRUPAL_ROOT . DIRECTORY_SEPARATOR . $config_list_file)) {
            try {
                $parsed = Yaml::decode($string);
            } catch (InvalidDataTypeException $e) {
                $this->io->error($this->trans('commands.config.export.list.messages.invalid-config-list-file-format'));
                return FALSE;
            }

            if (!isset($parsed['configs']) || !is_array($parsed['configs'])) {
                $this->io->error($this->trans('commands.config.export.list.messages.invalid-config-list-file-content'));
                return FALSE;
            }

            return $parsed['configs'];
        }
    }

    private function configImport($io, StorageComparer $storage_comparer)
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
            $io->success(
              $this->trans('commands.config.import.messages.already-imported')
            );
        } else {
            try {
                if ($config_importer->validate()) {
                    $sync_steps = $config_importer->initialize();

                    foreach ($sync_steps as $step) {
                        $context = array();
                        do {
                            $config_importer->doSyncStep($step, $context);
                        } while ($context['finished'] < 1);
                    }

                    return TRUE;
                }
            } catch (ConfigImporterException $e) {
                $message = 'The import failed due for the following reasons:'."\n";
                $message .= implode("\n", $config_importer->getErrors());
                $io->error(
                  sprintf(
                    $this->trans(
                      'commands.site.import.local.messages.error-writing'
                    ),
                    $message
                  )
                );
            } catch (\Exception $e) {
                $io->error(
                  sprintf(
                    $this->trans(
                      'commands.site.import.local.messages.error-writing'
                    ),
                    $e->getMessage()
                  )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $config_list_file = $input->getArgument('config-list-file');
        if (!$config_list_file) {
            $config_list_file = $io->ask(
              $this->trans(
                'commands.config.export.list.questions.config-list-file'
              ),
              '',
              function ($path) {
                  if (empty($path)) {
                      throw new \RuntimeException(
                        $this->trans(
                          'commands.config.export.list.messages.config-list-file-empty'
                        )
                      );
                  }

                  $full_path = DRUPAL_ROOT.DIRECTORY_SEPARATOR.$path;
                  if (!file_exists($full_path)) {
                      throw new \RuntimeException(
                        sprintf(
                          $this->trans(
                            'commands.config.export.list.messages.config-list-file-not-found'
                          ),
                          $full_path
                        )
                      );
                  }

                  return $path;
              }
            );

            $input->setArgument('config-list-file', $config_list_file);
        }
    }
}
