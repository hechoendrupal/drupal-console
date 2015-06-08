<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigExportCommand.
 */
namespace Drupal\AppConsole\Command;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigExportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('config:export')
          ->setDescription($this->trans('commands.config.export.description'))
          ->addArgument('directory', InputArgument::OPTIONAL,
            $this->trans('commands.config.export.arguments.directory'));
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

        $config_export_file = $directory.'/config.tar.gz';

        file_unmanaged_delete($config_export_file);

        try {
            $archiver = new ArchiveTar($config_export_file, 'gz');

            $this->configManager = $this->getConfigManager();
            // Get raw configuration data without overrides.
            foreach ($this->configManager->getConfigFactory()->listAll() as $name) {
                $archiver->addString("$name.yml",
                  Yaml::encode($this->configManager->getConfigFactory()->get($name)->getRawData()));
            }

            $this->targetStorage = $this->getConfigStorage();
            // Get all override data from the remaining collections.
            foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
                $collection_storage = $this->targetStorage->createCollection($collection);
                foreach ($collection_storage->listAll() as $name) {
                    $archiver->addString(str_replace('.', '/', $collection)."/$name.yml",
                      Yaml::encode($collection_storage->read($name)));
                }
            }
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$e->getMessage().'</error>');

            return;
        }

        $messageHelper->addSuccessMessage(
          sprintf($this->trans('commands.config.export.messages.directory'), $config_export_file)
        );
    }
}
