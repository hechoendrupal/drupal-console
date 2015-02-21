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
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\AppConsole\Config;

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
        $configFile = $path.$configName.'.yml';
        $ymlFile = new Parser();
        $fs = new Filesystem();

        try {
            $fs->mkdir($path);
            $fs->dumpFile($configFile, $this->getYamlConfig($configName));
        } catch (IOExceptionInterface $e) {
            throw new \IOException($this->trans('commands.config.edit.messages.no-directory')." ".$e->getPath());
        }

        $editor = $this->getEditor();
        $processBuilder = new ProcessBuilder(array($editor, $configFile));
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if ($process->isSuccessful()) {
            $value = $ymlFile->parse(file_get_contents($configFile));
            $config->setData($value);
            $config->save();
        }
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

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
        $consoleRoot = __DIR__.'/../../';
        $consoleConfig = new Config(new Parser(), $consoleRoot);
        $config = $consoleConfig->getConfig();
        $editor = ($config['application']['editor']) ? $config['application']['editor'] : '';

        if ($editor != '') {
            return trim($editor);
        }

        $processBuilder = new ProcessBuilder(array('bash'));
        $process = $processBuilder->getProcess();
        $process->setCommandLine('echo ${EDITOR:-${VISUAL:-vi}}');
        $process->run();
        $editor = $process->getOutput();
        $process->stop();

        return trim($editor);
    }
}
