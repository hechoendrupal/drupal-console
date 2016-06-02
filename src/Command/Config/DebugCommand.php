<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\DebugCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:debug')
            ->setDescription($this->trans('commands.config.debug.description'))
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.debug.arguments.name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('name');
        if (!$configName) {
            $this->getAllConfigurations($io);
        } else {
            $this->getConfigurationByName($io, $configName);
        }
    }

    /**
     * @param $io         DrupalStyle
     */
    private function getAllConfigurations(DrupalStyle $io)
    {
        $configFactory = $this->getDrupalService('config.factory');
        $names = $configFactory->listAll();
        $tableHeader = [
            $this->trans('commands.config.debug.arguments.name'),
        ];
        $tableRows = [];
        foreach ($names as $name) {
            $tableRows[] = [
                $name,
            ];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }

    /**
     * @param $io             DrupalStyle
     * @param $config_name    String
     */
    private function getConfigurationByName(DrupalStyle $io, $config_name)
    {
        $configStorage = $this->getDrupalService('config.storage');

        if ($configStorage->exists($config_name)) {
            $tableHeader = [
                $config_name,
            ];

            $configuration = $configStorage->read($config_name);
            $configurationEncoded = Yaml::encode($configuration);
            $tableRows = [];
            $tableRows[] = [
                $configurationEncoded,
            ];

            $io->table($tableHeader, $tableRows, 'compact');
        } else {
            $io->error(
                sprintf($this->trans('commands.config.debug.errors.not-exists'), $config_name)
            );
        }
    }
}
