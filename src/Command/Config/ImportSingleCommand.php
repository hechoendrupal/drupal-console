<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ImportSingleCommand.
 */
namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class ImportSingleCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:import:single')
            ->setDescription($this->trans('commands.config.import-single.description'))
            ->addArgument(
                'config-name', InputArgument::REQUIRED,
                $this->trans('commands.config.import-single.arguments.config-name')
            )
            ->addArgument(
                'input-file', InputArgument::OPTIONAL,
                $this->trans('commands.config.import-single.arguments.input-file')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('config-name');
        $fileName = $input->getArgument('input-file');
        $config = $this->getConfigFactory()->getEditable($configName);
        $ymlFile = new Parser();

        if (!empty($fileName) && file_exists($fileName)) {
            $value = $ymlFile->parse(file_get_contents($fileName));
        } else {
            $value = $ymlFile->parse(stream_get_contents(fopen("php://stdin", "r")));
        }

        if (empty($value)) {
            $io->error($this->trans('commands.config.import-single.messages.empty-value'));

            return;
        }

        $config->setData($value);
        $config->save();
    }

    /**
     * @param $config_name String
     *
     * @return array
     */
    protected function getYamlConfig($config_name)
    {
        $configStorage = $this->getConfigStorage();
        if ($configStorage->exists($config_name)) {
            $configuration = $configStorage->read($config_name);
            $configurationEncoded = Yaml::encode($configuration);
        }

        return $configurationEncoded;
    }
}
