<?php

namespace Drupal\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class Application
 * @package Drupal\Console
 */
class Application extends ConsoleApplication
{
    /**
     * @var string
     */
    const NAME = 'Drupal Console';

    /**
     * @var string
     */
    const VERSION = '1.0.0-rc1';

    public function __construct($container)
    {
        parent::__construct($this::NAME, $this::VERSION);
        $this->container = $container;
        $this->addOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->registerCommands();

        parent::doRun($input, $output);
    }

    private function addOptions()
    {
        $this->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, $this->trans('application.options.env'), $this->env)
        );
        $this->getDefinition()->addOption(
            new InputOption('--root', null, InputOption::VALUE_OPTIONAL, $this->trans('application.options.root'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--no-debug', null, InputOption::VALUE_NONE, $this->trans('application.options.no-debug'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--learning', null, InputOption::VALUE_NONE, $this->trans('application.options.learning'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-chain', '-c', InputOption::VALUE_NONE, $this->trans('application.options.generate-chain'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-inline', '-i', InputOption::VALUE_NONE, $this->trans('application.options.generate-inline'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--generate-doc', '-d', InputOption::VALUE_NONE, $this->trans('application.options.generate-doc'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--target', '-t', InputOption::VALUE_OPTIONAL, $this->trans('application.options.target'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--uri', '-l', InputOption::VALUE_REQUIRED, $this->trans('application.options.uri'))
        );
        $this->getDefinition()->addOption(
            new InputOption('--yes', '-y', InputOption::VALUE_NONE, $this->trans('application.options.yes'))
        );
    }

    private function registerCommands()
    {
        $consoleCommands = $this->container->getParameter('console.commands');

        foreach ($consoleCommands as $name) {
            if (!$this->container->has($name)) {
                continue;
            }

            $command = $this->container->get($name);
            if (!$command) {
                continue;
            }
            if (method_exists($command, 'setTranslator')) {
                $command->setTranslator(
                    $this->container->get('console.translator_manager')
                );
            }
            $this->add($command);
        }
    }

}
