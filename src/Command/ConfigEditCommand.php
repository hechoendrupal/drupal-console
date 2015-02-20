<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigEditCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\ProcessBuilder;

class ConfigEditCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
          ->setName('config:edit')
          ->setDescription($this->trans('commands.config.edit.description'))
          ->addArgument('config-name', InputArgument::REQUIRED,
            $this->trans('commands.config.edit.arguments.config-name'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('config-name');
        $config = $this->getConfigFactory()->getEditable($configName);
        $path = '/tmp/console/config/file/';
        $configFile = $path . $configName . '.yml';
        $yaml = new Parser();
        $fs = new Filesystem();

        try {
            $fs->mkdir($path);
            $fs->dumpFile($configFile, $this->getYamlConfig($configName));
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your directory at " . $e->getPath();
        }
        $editor = $this->getEditor();
        $processBuilder = new ProcessBuilder(array($editor, $configFile));
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        if ($process->isSuccessful()) {
            $value = $yaml->parse(file_get_contents($configFile));
            $config->setData($value);

            $config->save();
        }

        echo $process->getOutput();
    }

    /**
     * @param $config_name    String
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

    protected function getEditor()
    {
        $vim = new Process('which vim');
        $vim->run();
        if ($vim->getOutput() != '') {
            return 'vim';
        }

        $nano = new Process('which nano');
        $nano->run();
        if ($nano->getOutput() != '') {
            return 'nano';
        }

        $pico = new Process('which pico');
        $pico->run();
        if ($pico->getOutput() != '') {
            return 'pico';
        }
    }
}
