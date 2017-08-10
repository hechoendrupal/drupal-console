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
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ConfigurationManager;

class EditCommand extends Command
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * EditCommand constructor.
     *
     * @param ConfigFactory        $configFactory
     * @param CachedStorage        $configStorage
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ConfigFactory $configFactory,
        CachedStorage $configStorage,
        ConfigurationManager $configurationManager
    ) {
        $this->configFactory = $configFactory;
        $this->configStorage = $configStorage;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }
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
            )
            ->setAliases(['ced']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('config-name');
        $editor = $input->getArgument('editor');
        $config = $this->configFactory->getEditable($configName);
        $configSystem = $this->configFactory->get('system.file');
        $temporaryDirectory = $configSystem->get('path.temporary') ?: '/tmp';
        $configFile = $temporaryDirectory.'/config-edit/'.$configName.'.yml';
        $ymlFile = new Parser();
        $fileSystem = new Filesystem();

        if (!$configName) {
            $io->error($this->trans('commands.config.edit.messages.no-config'));

            return 1;
        }

        try {
            $fileSystem->mkdir($temporaryDirectory);
            $fileSystem->dumpFile($configFile, $this->getYamlConfig($configName));
        } catch (IOExceptionInterface $e) {
            $io->error($this->trans('commands.config.edit.messages.no-directory').' '.$e->getPath());

            return 1;
        }
        if (!$editor) {
            $editor = $this->getEditor();
        }
        $processBuilder = new ProcessBuilder([$editor, $configFile]);
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
            $io->error($process->getErrorOutput());
            return 1;
        }

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('config-name');
        if (!$configName) {
            $configNames = $this->configFactory->listAll();
            $configName = $io->choice(
                $this->trans('commands.config.edit.messages.choose-configuration'),
                $configNames
            );

            $input->setArgument('config-name', $configName);
        }
    }

    /**
     * @param $config_name String
     *
     * @return array
     */
    protected function getYamlConfig($config_name)
    {
        if ($this->configStorage->exists($config_name)) {
            $configuration = $this->configStorage->read($config_name);
            $configurationEncoded = Yaml::encode($configuration);
        }

        return $configurationEncoded;
    }

    /**
     * @return string
     */
    protected function getEditor()
    {
        $config = $this->configurationManager->getConfiguration();
        $editor = $config->get('application.editor', '');

        if ($editor != '') {
            return trim($editor);
        }

        $processBuilder = new ProcessBuilder(['bash']);
        $process = $processBuilder->getProcess();
        $process->setCommandLine('echo ${EDITOR:-${VISUAL:-vi}}');
        $process->run();
        $editor = $process->getOutput();
        $process->stop();

        return trim($editor);
    }
}
