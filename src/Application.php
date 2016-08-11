<?php

namespace Drupal\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->registerCommands();


        parent::doRun($input, $output);
    }

    private function registerCommands()
    {
        $consoleCommands = $this->container->getParameter('console.commands');

        var_export($consoleCommands);

        foreach ($consoleCommands as $name) {
            $command = null;
            if ($this->container->has($name)) {
                $command = $this->container->get($name);
            }

            if (!$command) {
                continue;
            }

            if (method_exists($command, 'setTranslator')) {
                $command->setTranslator($this->container->get('translator'));
            }

            $this->add($command);
        }
    }

}
