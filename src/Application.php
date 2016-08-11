<?php

namespace Drupal\Console;

//use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Output\OutputInterface;

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
}
