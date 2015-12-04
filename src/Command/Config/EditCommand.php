<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\EditCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class EditCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:edit')
            ->setDescription($this->trans('commands.config.edit.description'))
            ->addArgument(
                'config-name',
                InputArgument::REQUIRED,
                $this->trans('commands.config.edit.arguments.config-name')
            )
            ->addArgument(
                'editor',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.edit.arguments.editor')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('config-name');
        $editor = $input->getArgument('editor');
        $config = $this->getConfigFactory()->getEditable($configName);
        $configSystem = $this->getConfigFactory()->get('system.file');
        $temporaryDirectory = $configSystem->get('path.temporary') ?: '/tmp';
        $configFile = $temporaryDirectory.'/config-edit/'.$configName.'.yml';
        $ymlFile = new Parser();
        $fileSystem = new Filesystem();

        if (!$configName) {
            throw new \Exception($this->trans('commands.config.edit.messages.no-config'));
        }

        try {
            $fileSystem->mkdir($temporaryDirectory);
            $fileSystem->dumpFile($configFile, $this->getYamlConfig($configName));
        } catch (IOExceptionInterface $e) {
            throw new \Exception($this->trans('commands.config.edit.messages.no-directory').' '.$e->getPath());
        }
        if (!$editor) {
            $editor = $this->getEditor();
        }
        $processBuilder = new ProcessBuilder(array($editor, $configFile));
        $process = $processBuilder->getProcess();
        $process->setTty('true');
        $process->run();

        if ($process->isSuccessful()) {
            $value = $ymlFile->parse(file_get_contents($configFile));
            $config->setData($value);
            $config->save();
            $fileSystem->remove($configFile);
        }
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $configName = $input->getArgument('config-name');
        if (!$configName) {
            $configFactory = $this->getConfigFactory();
            $configNames = $configFactory->listAll();
            $configName = $output->choice(
                'Choose a configuration',
                $configNames
            );
        }

        $input->setArgument('config-name', $configName);
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

    /**
     * @return string
     */
    protected function getEditor()
    {
        $app = $this->getApplication();
        $config = $app->getConfig();
        $editor = $config->get('application.editor', 'vi');

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
