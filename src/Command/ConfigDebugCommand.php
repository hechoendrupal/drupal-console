<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;

class ConfigDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:debug')
            ->setDescription($this->trans('commands.config.debug.description'))
            ->addArgument(
                'config-name',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.debug.arguments.config-name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config_name = $input->getArgument('config-name');

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        if (!$config_name) {
            $this->getAllConfigurations($output, $table);
        } else {
            $this->getConfigurationByName($output, $table, $config_name);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     */
    private function getAllConfigurations($output, $table)
    {
        $configFactory = $this->getConfigFactory();
        $names = $configFactory->listAll();
        $table->setHeaders([$this->trans('commands.config.debug.arguments.config-name')]);
        foreach ($names as $name) {
            $table->addRow([$name]);
        }
        $table->render($output);
    }

    /**
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $config_name    String
     */
    private function getConfigurationByName($output, $table, $config_name)
    {
        $configStorage = $this->getConfigStorage();
        if ($configStorage->exists($config_name)) {
            $table->setHeaders([$config_name]);

            $configuration = $configStorage->read($config_name);
            $configurationEncoded = Yaml::encode($configuration);

            $table->addRow([$configurationEncoded]);
        }
        $table->render($output);
    }
}
