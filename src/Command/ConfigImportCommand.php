<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigImportCommand.
 */

namespace Drupal\AppConsole\Command;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\FileStorage;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class ConfigImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:import')
            ->setDescription($this->trans('commands.config.import.description'))
            ->addArgument(
                'config-file', InputArgument::REQUIRED,
                $this->trans('commands.config.import.arguments.config-file')
            )
            ->addOption('copy-only', '', InputOption::VALUE_NONE, $this->trans('commands.config.import.arguments.copy-only'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config_file = $input->getArgument('config-file');
        $copy_only = $input->getOption('copy-only');

        try {
            $files = array();
            $archiver = new ArchiveTar($config_file, 'gz');

            $output->writeln($this->trans('commands.config.import.messages.config_files_imported'));
            foreach ($archiver->listContent() as $file) {
                $pathinfo = pathinfo($file['filename']);
                $files[$pathinfo['filename']] = $file['filename'];
                $output->writeln('[-] <info>' .  $file['filename'] . '</info>');
            }

            $config_staging_dir = config_get_config_directory(CONFIG_STAGING_DIRECTORY);

            try {
                $archiver->extract($config_staging_dir . '/');
            } catch (\Exception $e) {
                $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
                return;
            }

            if ($copy_only) {
                $output->writeln(sprintf($this->trans('commands.config.import.messages.copied'), CONFIG_STAGING_DIRECTORY));
            } else {
                foreach ($files as $cofig_name => $filename) {
                    $config = $this->getConfigFactory()->getEditable($cofig_name);
                    $parser = new Parser();
                    $config_value = $parser->parse(file_get_contents($config_staging_dir . '/' . $filename));
                    $config->setData($config_value);

                    try {
                        $config->save();
                    } catch (\Exception $e) {
                        $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
                        return;
                    }
                }

                $output->writeln(sprintf($this->trans('commands.config.import.messages.imported'), CONFIG_STAGING_DIRECTORY));
            }
        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;
        }
    }
}
