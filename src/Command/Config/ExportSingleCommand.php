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
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\ContainerAwareCommand;

class ExportSingleCommand extends ContainerAwareCommand
{
    /**
     * @var \Drupal\Core\Entity\EntityManager
     */
    protected $entityManager;

    /**
     * @var []
     */
    protected $definitions;

    /**
     * @var \Drupal\Core\Config\StorageInterface
     */
    protected $configStorage;

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
            );
    }

    /*
     * Return config types
     */
    protected function getConfigTypes()
    {
        $this->entityManager = $this->getService('entity_type.manager');

        foreach ($this->entityManager->getService('entity_type.manager') as $entity_type => $definition) {
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
        $this->configStorage = $this->getConfigStorage();

        // For a given entity type, load all entities.
        if ($config_type && $config_type !== 'system.simple') {
            $entity_storage = $this->entityManager->getStorage($config_type);
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
                $definition = $this->entityManager->getDefinition($config_type);
                $config_name = $definition->getConfigPrefix() . '.' . $config_name;
            }

            $input->setArgument('config-name', $config_name);
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getOption('directory');

        if (!$directory) {
            $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $configName = $input->getArgument('config-name');

        $configNames = [$configName];
        if ($input->getOption('include-dependencies')) {
            $configNames += $this->getConfigDependencies($configName);
        }
        foreach ($configNames as $configName) {
            $config = $this->getConfigFactory()->getEditable($configName);

            $configExportFile = $directory . '/' . $configName.'.yml';

            file_unmanaged_delete($configExportFile);

            if ($config) {
                $yaml = Yaml::encode($config->getRawData());
                // Save configuration file.
                file_put_contents($configExportFile, $yaml);
                $io->info(
                    sprintf($this->trans('commands.config.export.single.messages.export'), $configExportFile)
                );
            } else {
                $io->error($this->trans('commands.config.export.single.messages.config-not-found'));
            }
        }
    }

    /**
     * Returns all configuration depedencies for a configuration item.
     *
     * @param string $configName
     *   The name of the configuration item to get dependencies for.
     *
     * @return array
     *   An array of dependent configuration item names.
     */
    protected function getConfigDependencies($configName)
    {
        $dependencyManager = $this->getConfigManager()->getConfigDependencyManager();
        // Compute dependent config.
        $dependent_list = $dependencyManager->getDependentEntities('config', $configName);
        $dependents = [];
        foreach ($dependent_list as $config_name => $item) {
            if (!isset($dependents[$config_name])) {
                $dependents[$config_name] = $config_name;
            }
            // Grab any dependent graph paths.
            if (isset($item['reverse_paths'])) {
                foreach ($item['reverse_paths'] as $dependent_name => $value) {
                    if ($value && !isset($dependents[$dependent_name])) {
                        $dependents[$dependent_name] = $dependent_name;
                    }
                }
            }
        }

        return $dependents;
    }
}
