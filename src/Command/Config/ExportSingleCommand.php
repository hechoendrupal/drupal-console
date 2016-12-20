<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportSingleCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ExportTrait;

class ExportSingleCommand extends Command
{
    use CommandTrait;
    use ExportTrait;

    /**
     * @var []
     */
    protected $definitions;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    protected $configExport;

    /**
     * ExportSingleCommand constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param CachedStorage              $configStorage
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CachedStorage $configStorage
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->configStorage = $configStorage;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:export:single')
            ->setDescription($this->trans('commands.config.export.single.description'))
            ->addOption(
                'name',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.config.export.single.options.name')
            )->addOption(
                'directory',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.arguments.directory')
            )->addOption(
                'module',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.common.options.module')
            )->addOption(
                'include-dependencies',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.single.options.include-dependencies')
            )->addOption(
                'optional',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.single.options.optional')
            )->addOption(
                'remove-uuid',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.single.options.remove-uuid')
            )->addOption(
                'remove-config-hash',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.single.options.remove-config-hash')
            );
    }

    /*
     * Return config types
     */
    protected function getConfigTypes()
    {
        foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
            if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
                $this->definitions[$entity_type] = $definition;
            }
        }
        $entity_types = array_map(
            function ($definition) {
                return $definition->getLabel();
            }, $this->definitions
        );

        uasort($entity_types, 'strnatcasecmp');
        $config_types = array(
            'system.simple' => $this->trans('commands.config.export.single.options.simple-configuration'),
          ) + $entity_types;

        return $config_types;
    }

    /*
     * Return config types
     */
    protected function getConfigNames($config_type)
    {
        $names = [];
        // For a given entity type, load all entities.
        if ($config_type && $config_type !== 'system.simple') {
            $entity_storage = $this->entityTypeManager->getStorage($config_type);
            foreach ($entity_storage->loadMultiple() as $entity) {
                $entity_id = $entity->id();
                $label = $entity->label() ?: $entity_id;
                $names[$entity_id] = $label;
            }
        }
        // Handle simple configuration.
        else {
            // Gather the config entity prefixes.
            $config_prefixes = array_map(
                function ($definition) {
                    return $definition->getConfigPrefix() . '.';
                }, $this->definitions
            );

            // Find all config, and then filter our anything matching a config prefix.
            $names = $this->configStorage->listAll();
            $names = array_combine($names, $names);
            foreach ($names as $config_name) {
                foreach ($config_prefixes as $config_prefix) {
                    if (strpos($config_name, $config_prefix) === 0) {
                        unset($names[$config_name]);
                    }
                }
            }
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $config_types = $this->getConfigTypes();

        $name = $input->getOption('name');
        if (!$name) {
            $type = $io->choiceNoList(
                $this->trans('commands.config.export.single.questions.config-type'),
                array_keys($config_types),
                'system.simple'
            );
            $names = $this->getConfigNames($type);

            $name = $io->choiceNoList(
                $this->trans('commands.config.export.single.questions.name'),
                array_keys($names)
            );

            if ($type !== 'system.simple') {
                $definition = $this->entityTypeManager->getDefinition($type);
                $name = $definition->getConfigPrefix() . '.' . $name;
            }
            $input->setOption('name', $name);
        }

        $module = $input->getOption('module');
        if ($module) {
            $optionalConfig = $input->getOption('optional');
            if (!$optionalConfig) {
                $optionalConfig = $io->confirm(
                    $this->trans('commands.config.export.single.questions.optional'),
                    true
                );
                $input->setOption('optional', $optionalConfig);
            }
        }

        if (!$input->getOption('remove-uuid')) {
            $removeUuid = $io->confirm(
                $this->trans('commands.config.export.single.questions.remove-uuid'),
                true
            );
            $input->setOption('remove-uuid', $removeUuid);
        }
        if (!$input->getOption('remove-config-hash')) {
            $removeHash = $io->confirm(
                $this->trans('commands.config.export.single.questions.remove-config-hash'),
                true
            );
            $input->setOption('remove-config-hash', $removeHash);
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getOption('directory');
        $module = $input->getOption('module');
        $ame = $input->getOption('name');
        $optional = $input->getOption('optional');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');

        foreach ($ame as $nameItem) {
            $config = $this->getConfiguration(
                $nameItem,
                $removeUuid,
                $removeHash
            );
            
            if ($config) {
                $this->configExport[$nameItem] = [
                    'data' => $config,
                    'optional' => $optional
                ];

                if ($input->getOption('include-dependencies')) {
                    // Include config dependencies in export files
                    if ($dependencies = $this->fetchDependencies($config, 'config')) {
                        $this->resolveDependencies($dependencies, $optional);
                    }
                }
            } else {
                $io->error($this->trans('commands.config.export.single.messages.config-not-found'));
            }
        }

        if ($module) {
            $this->exportConfigToModule(
                $module,
                $io,
                $this->trans(
                    'commands.config.export.single.messages.config-exported'
                )
            );

            return 0;
        }

        if (!$directory) {
            $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        $this->exportConfig(
            $directory,
            $io,
            $this->trans('commands.config.export.single.messages.config-exported')
        );

        return 0;
    }
}
