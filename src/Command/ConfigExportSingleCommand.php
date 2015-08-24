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
