<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class ExportCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:export')
            ->setDescription($this->trans('commands.config.export.description'))
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.arguments.directory')
            )
            ->addOption(
                'tar',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.arguments.tar')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = $input->getOption('directory');
        $tar = $input->getOption('tar');
        $archiveTar = new ArchiveTar();

        if (!$directory) {
            $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
        }

        if ($tar) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $dateTime = new \DateTime();

            $archiveFile = sprintf(
                '%s/config-%s.tar.gz',
                $directory,
                $dateTime->format('Y-m-d-H-i-s')
            );
            $archiveTar = new ArchiveTar($archiveFile, 'gz');
        }

        try {
            $configManager = $this->getDrupalService('config.manager');
            // Get raw configuration data without overrides.
            foreach ($configManager->getConfigFactory()->listAll() as $name) {
                $configData = $configManager->getConfigFactory()->get($name)->getRawData();
                $configName =  sprintf('%s.yml', $name);
                $ymlData = Yaml::encode($configData);

                if ($tar) {
                    $archiveTar->addString(
                        $configName,
                        $ymlData
                    );
                    continue;
                }

                $configFileName =  sprintf('%s/%s', $directory, $configName);

                $fs = $this->get('filesystem');
                try {
                    $fs->mkdir($directory);
                } catch (IOExceptionInterface $e) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.config.export.messages.error'),
                            $e->getPath()
                        )
                    );
                }
                file_put_contents($configFileName, $ymlData);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        $io->success($this->trans('commands.config.export.messages.directory'));
        $io->simple($directory);
    }
}
