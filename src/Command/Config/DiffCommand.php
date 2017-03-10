<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DiffCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigManager;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class DiffCommand extends Command
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
     * DiffCommand constructor.
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
     * A static array map of operations -> color strings.
     *
     * @see http://symfony.com/doc/current/components/console/introduction.html#coloring-the-output
     *
     * @var array
     */
    protected static $operationColours = [
        'delete' => '<fg=red>%s</fg=red>',
        'update' => '<fg=yellow>%s</fg=yellow>',
        'create' => '<fg=green>%s</fg=green>',
        'default' => '%s',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:diff')
            ->setDescription($this->trans('commands.config.diff.description'))
            ->addArgument(
                'directory',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.diff.arguments.directory')
            )
            ->addOption(
                'reverse',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.diff.options.reverse')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        global $config_directories;
        $io = new DrupalStyle($input, $output);

        $directory = $input->getArgument('directory');
        if (!$directory) {
            $directory = $io->choice(
                $this->trans('commands.config.diff.questions.directories'),
                array_keys($config_directories),
                CONFIG_SYNC_DIRECTORY
            );

            $input->setArgument('directory', $config_directories[$directory]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $directory = $input->getArgument('directory');
        $source_storage = new FileStorage($directory);

        if ($input->getOption('reverse')) {
            $config_comparer = new StorageComparer($source_storage, $this->configStorage, $this->configManager);
        } else {
            $config_comparer = new StorageComparer($this->configStorage, $source_storage, $this->configManager);
        }
        if (!$config_comparer->createChangelist()->hasChanges()) {
            $output->writeln($this->trans('commands.config.diff.messages.no-changes'));
        }

        $change_list = [];
        foreach ($config_comparer->getAllCollectionNames() as $collection) {
            $change_list[$collection] = $config_comparer->getChangelist(null, $collection);
        }

        $this->outputDiffTable($io, $change_list);
    }

    /**
     * Ouputs a table of configuration changes.
     *
     * @param DrupalStyle $io
     *   The io.
     * @param array       $change_list
     *   The list of changes from the StorageComparer.
     */
    protected function outputDiffTable(DrupalStyle $io, array $change_list)
    {
        $header = [
            $this->trans('commands.config.diff.table.headers.collection'),
            $this->trans('commands.config.diff.table.headers.config-name'),
            $this->trans('commands.config.diff.table.headers.operation'),
        ];
        $rows = [];
        foreach ($change_list as $collection => $changes) {
            foreach ($changes as $operation => $configs) {
                foreach ($configs as $config) {
                    $rows[] = [
                        $collection,
                        $config,
                        sprintf(self::$operationColours[$operation], $operation),
                    ];
                }
            }
        }
        $io->table($header, $rows);
    }
}
