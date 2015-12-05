<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\DebugCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends ContainerAwareCommand
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
        $output = new DrupalStyle($input, $output);

        $table = new Table($output);
        $table->setStyle('compact');

        $configName = $input->getArgument('config-name');
        if (!$configName) {
            $this->getAllConfigurations($output, $table);
        } else {
            $this->getConfigurationByName($output, $table, $configName);
        }
    }

    /**
     * @param $output         OutputInterface
     * @param $table          Table
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
