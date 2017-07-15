<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportSingleCommand.
 */
namespace Drupal\Console\Command\Config;

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\StorageComparer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Webmozart\PathUtil\Path;

class ImportSingleCommand extends Command
{
    use CommandTrait;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * ImportSingleCommand constructor.
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
            ->setName('config:import:single')
            ->setDescription($this->trans('commands.config.import.single.description'))
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.config.import.single.options.file')
            )->addOption(
                'directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.import.arguments.directory')
            )
            ->setAliases(['cis']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $file = $input->getOption('file');
        $directory = $input->getOption('directory');

        if (!$file) {
            $io->error($this->trans('commands.config.import.single..message.missing-file'));

            return 1;
        }

        if ($directory) {
            $directory = Path::canonicalize($directory);
        }

        $names = [];
        try {
            $source_storage = new StorageReplaceDataWrapper(
                $this->configStorage
            );

            foreach ($file as $fileItem) {
                $configFile = $fileItem;
                if ($directory) {
                    $configFile = Path::canonicalize($directory) . '/' . $fileItem;
                }

                if (file_exists($configFile)) {
                    $name = Path::getFilenameWithoutExtension($configFile);
                    $ymlFile = new Parser();
                    $value = $ymlFile->parse(file_get_contents($configFile));
                    $source_storage->delete($name);
                    $source_storage->write($name, $value);
                    $names[] = $name;
                    continue;
                }

                $io->error($this->trans('commands.config.import.single.messages.empty-value'));
                return 1;
            }

            $storageComparer = new StorageComparer(
                $source_storage,
                $this->configStorage,
                $this->configManager
            );

            if ($this->configImport($io, $storageComparer)) {
                $io->success(
                    sprintf(
                        $this->trans(
                            'commands.config.import.single.messages.success'
                        ),
                        implode(',', $names)
                    )
                );
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
    }

    private function configImport($io, StorageComparer $storageComparer)
    {
        $configImporter = new ConfigImporter(
            $storageComparer,
            \Drupal::service('event_dispatcher'),
            \Drupal::service('config.manager'),
            \Drupal::lock(),
            \Drupal::service('config.typed'),
            \Drupal::moduleHandler(),
            \Drupal::service('module_installer'),
            \Drupal::service('theme_handler'),
            \Drupal::service('string_translation')
        );

        if ($configImporter->alreadyImporting()) {
            $io->success($this->trans('commands.config.import.messages.already-imported'));
        } else {
            try {
                if ($configImporter->validate()) {
                    $sync_steps = $configImporter->initialize();

                    foreach ($sync_steps as $step) {
                        $context = [];
                        do {
                            $configImporter->doSyncStep($step, $context);
                        } while ($context['finished'] < 1);
                    }

                    return true;
                }
            } catch (ConfigImporterException $e) {
                $message = $this->trans('commands.config.import.messages.import-fail') . "\n";
                $message .= implode("\n", $configImporter->getErrors());
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

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = $input->getOption('file');
        $directory = $input->getOption('directory');

        if (!$file) {
            $file = $io->ask(
                $this->trans('commands.config.import.single.questions.file')
            );
            $input->setOption('file', [$file]);

            if (!$directory && !Path::isAbsolute($file)) {
                $directory = $io->ask(
                    $this->trans('commands.config.import.single.questions.directory')
                );

                $input->setOption('directory', $directory);
            }
        }
    }
}
