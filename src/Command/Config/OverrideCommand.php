<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\OverrideCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactory;

class OverrideCommand extends Command
{
    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * OverrideCommand constructor.
     *
     * @param CachedStorage $configStorage
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        CachedStorage $configStorage,
        ConfigFactory $configFactory
    ) {
        $this->configStorage = $configStorage;
        $this->configFactory = $configFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('config:override')
            ->setDescription($this->trans('commands.config.override.description'))
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                $this->trans('commands.config.override.arguments.name')
            )
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                $this->trans('commands.config.override.arguments.key')
            )
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                $this->trans('commands.config.override.arguments.value')
            )
            ->setAliases(['co']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $names = $this->configFactory->listAll();
        if ($name) {
            if (!in_array($name, $names)) {
                $this->getIo()->warning(
                    sprintf(
                        $this->trans('commands.config.override.messages.invalid-name'),
                        $name
                    )
                );
                $name = null;
            }
        }
        if (!$name) {
            $name = $this->getIo()->choiceNoList(
                $this->trans('commands.config.override.questions.name'),
                $names
            );
            $input->setArgument('name', $name);
        }
        $key = $input->getArgument('key');
        if (!$key) {
            if ($this->configStorage->exists($name)) {
                $configuration = $this->configStorage->read($name);
            }
            $key = $this->getIo()->choiceNoList(
                $this->trans('commands.config.override.questions.key'),
                array_keys($configuration)
            );
            $input->setArgument('key', $key);
        }
        $value = $input->getArgument('value');
        if (!$value) {
            $value = $this->getIo()->ask(
                $this->trans('commands.config.override.questions.value')
            );
            $input->setArgument('value', $value);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('name');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $config = $this->configFactory->getEditable($configName);

        $configurationOverrideResult = $this->overrideConfiguration(
            $config,
            $key,
            $value
        );

        $config->save();

        $this->getIo()->info($this->trans('commands.config.override.messages.configuration'), false);
        $this->getIo()->comment($configName);

        $tableHeader = [
            $this->trans('commands.config.override.messages.configuration-key'),
            $this->trans('commands.config.override.messages.original'),
            $this->trans('commands.config.override.messages.updated'),
        ];
        $tableRows = $configurationOverrideResult;
        $this->getIo()->table($tableHeader, $tableRows);
    }


    protected function overrideConfiguration($config, $key, $value)
    {
        $result[] = [
            'configuration' => $key,
            'original' => $config->get($key),
            'updated' => $value,
        ];
        $config->set($key, $value);

        return $result;
    }
}
