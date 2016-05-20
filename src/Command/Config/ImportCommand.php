<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Console\Style\DrupalStyle;

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
                $this->trans('commands.config.import.arguments.file')
            )
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.import.arguments.directory')
            )
            ->addOption(
                'remove-files',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.import.arguments.remove-files')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $archiveFile = $input->getOption('file');
        $directory = $input->getOption('directory');
        $removeFiles = $input->getOption('remove-files');

        if ($directory) {
            $configSyncDir = $directory;
        } else {
            $configSyncDir = config_get_config_directory(
                CONFIG_SYNC_DIRECTORY
            );
        }

        if ($archiveFile) {
            $this->extractArchive($io, $archiveFile, $configSyncDir);
        }

        $finder = new Finder();
        $finder->in($configSyncDir);
        $finder->name("*.yml");

        foreach ($finder as $configFile) {
            $configName = $configFile->getBasename('.yml');
            $configFilePath = sprintf(
                '%s/%s',
                $configSyncDir,
                $configFile->getBasename()
            );
            $config = $this->getDrupalService('config.factory')->getEditable($configName);
            $parser = new Parser();
            $configData = $parser->parse(
                file_get_contents($configFilePath)
            );

            $config->setData($configData);

            if ($removeFiles) {
                file_unmanaged_delete($configFilePath);
            }

            try {
                $config->save();
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return;
            }
        }

        $io->success($this->trans('commands.config.import.messages.imported'));
    }

    /**
     * Extracts the contents of the archive file into the config directory.
     *
     * @param DrupalStyle $io
     *   IO object to print messages.
     * @param string      $archiveFile
     *   The archive file to extract
     * @param string      $configDir
     *   The directory to extract the files into.
     *
     * @return \Drupal\Core\Archiver\ArchiveTar
     *   The initialised object.
     *
     * @throws \Exception
     *   If something went wrong during extraction.
     */
    private function extractArchive(DrupalStyle $io, $archiveFile, $configDir)
    {
        $archiveTar = new ArchiveTar($archiveFile, 'gz');

        $io->simple(
            $this->trans(
                'commands.config.import.messages.config_files_imported'
            )
        );

        foreach ($archiveTar->listContent() as $file) {
            $io->info(
                '[-] ' . $file['filename']
            );
        }

        try {
            $archiveTar->extract($configDir . '/');
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return;
        }
    }
}
