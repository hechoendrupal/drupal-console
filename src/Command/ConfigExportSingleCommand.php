<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigExportCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigExportSingleCommand extends ContainerAwareCommand
{
    protected $entityManager;
    protected $definitions;
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
            ->addArgument(
                'directory',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.export.arguments.directory')
            );
    }

    /*
     * Return config types
     */
    protected function getConfigTypes()
    {
        $this->entityManager = $this->getEntityManager();

        foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
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
        $dialog = $this->getDialogHelper();
        $utils = $this->getStringUtils();

        $config_types = $this->getConfigTypes();

        $config_name = $input->getArgument('config-name');
        if (!$config_name) {
            // Type input
            $config_type = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion('  '. $this->trans('commands.config.export.single.questions.config-type'), $this->trans('commands.config.export.single.options.simple-configuration'), ':'),
                function ($input) use ($config_types) {
                    if (!in_array($input, $config_types)) {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.config.export.single.messages.invalid-config-type'), $input)
                        );
                    }

                    return $input;
                },
                false,
                $this->trans('commands.config.export.single.options.simple-configuration'),
                $config_types
            );

            $config_type_key = array_search($config_type, $config_types);

            $config_names = $this->getConfigNames($config_type_key);

            $config_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion('  '. $this->trans('commands.config.export.single.questions.config-name'), current($config_names), ':'),
                function ($input) use ($config_names) {
                    if (!in_array($input, $config_names)) {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.config.export.single.messages.invalid-config-name'), $input)
                        );
                    }

                    return $input;
                },
                false,
                current($config_names),
                $config_names
            );

            // Calculate internal config ID
            $config_name_key = array_search($config_name, $config_names);

            if ($config_type_key !== 'system.simple') {
                $definition = $this->entityManager->getDefinition($config_type_key);
                $name = $definition->getConfigPrefix() . '.' . $config_name_key;
            }
            // The config name is used directly for simple configuration.
            else {
                $name =$config_name_key;
            }

            $input->setArgument('config-name', $name);
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messageHelper = $this->getHelperSet()->get('message');
        $directory = $input->getArgument('directory');

        if (!$directory) {
            $config = $this->getConfigFactory()->get('system.file');
            $directory = $config->get('path.temporary') ?: file_directory_temp();
            $directory .= '/'.CONFIG_STAGING_DIRECTORY;
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $config_name = $input->getArgument('config-name');
        $config_export_file = $directory . '/' . $config_name.'.yml';

        file_unmanaged_delete($config_export_file);

        $config = $this->getConfigFactory()->getEditable($config_name);

        if ($config) {
            $yaml = Yaml::encode($config->getRawData());
            // Save release file
            file_put_contents($config_export_file, $yaml);
            $output->writeln('[+] <info>'.sprintf($this->trans('commands.config.export.single.messages.export'), $config_export_file).'</info>');
        } else {
            $output->writeln('[+] <error>'.$this->trans('commands.config.export.single.messages.config-not-found').'</error>');
        }
    }
}
