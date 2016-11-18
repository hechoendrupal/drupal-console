<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportSingleCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportListCommand extends Command {
  use CommandTrait;
  use ExportTrait;

  /**
   * ExportListCommand constructor.
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param CachedStorage $configStorage
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
  protected function configure() {
    $this
      ->setName('config:export:list')
      ->setDescription($this->trans('commands.config.export.single.description'))
      ->addArgument(
        'config-list-file',
        InputArgument::REQUIRED,
        $this->trans('commands.config.export.list.arguments.config-list-file')
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
        $this->trans('commands.config.export.list.options.include-dependencies')
      )->addOption(
        'module', '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.common.options.module')
      )->addOption(
        'optional-config',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.config.export.list.options.optional-config')
      )->addOption(
        'remove-uuid',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.config.export.list.options.remove-uuid'),
        FALSE
      );
  }

  /*
   * Return config types
   */
  protected function getConfigTypes() {
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
  protected function getConfigNames($config_type) {

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
  protected function interact(InputInterface $input, OutputInterface $output) {
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

    $module = $input->getOption('module');

    if ($module) {
      $optionalConfig = $input->getOption('optional-config');
      if (!$optionalConfig) {
        $optionalConfig = $io->confirm(
          $this->trans('commands.config.export.single.questions.optional-config'),
          TRUE
        );
        $input->setOption('optional-config', $optionalConfig);
      }
    }
    if (!$input->getOption('remove-uuid')) {
      $removeUuid = $io->confirm(
        $this->trans('commands.config.export.single.questions.remove-uuid'),
        TRUE
      );
      $input->setOption('remove-uuid', $removeUuid);
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

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $configListFile = $input->getArgument('config-list-file');
    $directory = $input->getOption('directory');
    $module = $input->getOption('module');
    $optionalConfig = $input->getOption('optional-config');
    $removeUuid = $input->getOption('remove-uuid');

    $configNames = $this->parseYMLConfigListFile($configListFile);

    foreach ($configNames as $configName) {
      if (!$removeUuid) {
        $config = $this->getConfiguration($configName, TRUE);
      }
      else {
        $config = $this->getConfiguration($configName, FALSE);
      }
      if ($config) {
        if (!$directory) {
          $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        $this->configExport[$configName] = array(
          'data' => $config,
          'optional' => $optionalConfig
        );

        if ($input->getOption('include-dependencies')) {
          // Include config dependencies in export files
          if ($dependencies = $this->fetchDependencies($config, 'config')) {
            $this->resolveDependencies($dependencies, $optionalConfig);
          }
        }
      }
      else {
        $io->error($this->trans('commands.config.export.list.messages.config-not-found'));
      }

      if (!$module) {
        if (!$directory) {
          $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        $this->exportConfig($directory, $io, $this->trans('commands.config.export.list.messages.config_exported'));
      }
      else {
        $this->exportConfigToModule($module, $io, $this->trans('commands.config.export.list.messages.config_exported'));
      }
    }
  }
}
