<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigImportSingleCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Drupal\Component\Serialization\Yaml;

class ConfigImportSingleCommand extends ContainerAwareCommand
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
            $output->writeln(
              sprintf(
                "<error>%s</error>",
                $this->trans('commands.config.import-single.messages.empty-value')
              )
            );
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
