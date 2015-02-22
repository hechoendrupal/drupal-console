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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
          ->addArgument('config-file', InputArgument::REQUIRED,
            $this->trans('commands.config.import.arguments.config-file'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config_file = $input->getArgument('config-file');

        try {
            $files = array();
            $archiver = new ArchiveTar($config_file, 'gz');

            $this->showMessage($output, $this->trans('commands.config.import.messages.config_files_imported'));
            foreach ($archiver->listContent() as $file) {
              $files[] = $file['filename'];
              print  $file['filename'] . "\n";
            }
            $archiver->extractList($files, config_get_config_directory(CONFIG_STAGING_DIRECTORY));



        } catch (\Exception $e) {
            $output->writeln('[+] <error>' . $e->getMessage() . '</error>');
            return;
        }

        $this->showMessage($output,
            sprintf($this->trans('commands.config.import.messages.imported'), CONFIG_STAGING_DIRECTORY));
    }
}
