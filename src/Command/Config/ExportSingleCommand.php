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

    /** @var EntityTypeManagerInterface  */
    protected $entityTypeManager;

    /** @var CachedStorage  */
    protected $configStorage;

    protected $configExport;

    /**
     * ExportSingleCommand constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param CachedStorage     $configStorage
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
          ->addArgument(
            'config-name',
            InputArgument::REQUIRED,
            $this->trans('commands.config.export.single.arguments.config-name')
          )
          ->addOption(
            'directory',
            '',
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.config.export.arguments.directory')
          )
          ->addOption(
            'include-dependencies',
            '',
            InputOption::VALUE_NONE,
            $this->trans('commands.config.export.single.options.include-dependencies')
          )->addOption(
            'module', '',
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.common.options.module')
          )->addOption(
            'optional-config',
            '',
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.config.export.single.options.optional-config')
          )->addOption(
            'remove-uuid',
            '',
            InputOption::VALUE_NONE,
            $this->trans('commands.config.export.single.options.remove-uuid')
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

        $config_name = $input->getArgument('config-name');
        if (!$config_name) {
            $config_type = $io->choiceNoList(
              $this->trans('commands.config.export.single.questions.config-type'),
              array_keys($config_types),
              $this->trans('commands.config.export.single.options.simple-configuration')
            );
            $config_names = $this->getConfigNames($config_type);

            $config_name = $io->choiceNoList(
              $this->trans('commands.config.export.single.questions.config-name'),
              array_keys($config_names)
            );

            if ($config_type !== 'system.simple') {
                $definition = $this->entityTypeManager->getDefinition($config_type);
                $config_name = $definition->getConfigPrefix() . '.' . $config_name;
            }

            $input->setArgument('config-name', $config_name);
        }

        $module = $input->getOption('module');

        if ($module) {
            $optionalConfig = $input->getOption('optional-config');
            if (!$optionalConfig) {
                $optionalConfig = $io->confirm(
                  $this->trans('commands.config.export.single.questions.optional-config'),
                  true
                );
                $input->setOption('optional-config', $optionalConfig);
            }
        }
        if (!$input->getOption('remove-uuid')) {
            $removeUuid = $io->confirm(
              $this->trans('commands.config.export.single.questions.remove-uuid'),
              true
            );
            $input->setOption('remove-uuid', $removeUuid);
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
        $configName = $input->getArgument('config-name');
        $optionalConfig = $input->getOption('optional-config');
        $removeUuid = $input->getOption('remove-uuid');

        if (!$removeUuid) {
            $config = $this->getConfiguration($configName, true);
        } else {
            $config = $this->getConfiguration($configName, false);
        }
        if ($config) {
            if (!$directory) {
                $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
            }

            $this->configExport[$configName] = array('data' => $config, 'optional' => $optionalConfig);

            if ($input->getOption('include-dependencies')) {
                // Include config dependencies in export files
                if ($dependencies = $this->fetchDependencies($config, 'config')) {
                    $this->resolveDependencies($dependencies, $optionalConfig);
                }
            }
        } else {
            $io->error($this->trans('commands.config.export.single.messages.config-not-found'));
        }

        if (!$module) {
            if (!$directory) {
                $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
            }

            $this->exportConfig($directory, $io, $this->trans('commands.config.export.single.messages.config_exported'));
        } else {
            $this->exportConfigToModule($module, $io, $this->trans('commands.config.export.single.messages.config_exported'));
        }
    }
}
