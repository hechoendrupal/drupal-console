<?php

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Console\Core\Utils\DrupalFinder;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class DotenvCommand extends Command
{
    /**
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * InitCommand constructor.
     *
     * @param DrupalFinder $drupalFinder
     */
    public function __construct(
        DrupalFinder $drupalFinder
    ) {
        $this->drupalFinder = $drupalFinder;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('debug:dotenv')
            ->setDescription('Debug Dotenv debug values.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $envFile = $this->drupalFinder->getComposerRoot() . '/.env';
        if (!$fs->exists($envFile)) {
            $this->getIo()->warning('File '. $envFile . ' not found.');

            return 1;
        }

        $fileContent = file_get_contents($envFile);
        $this->getIo()->writeln($fileContent);

        $this->getIo()->warning('This command is deprecated use instead: `cat .env`');
    }
}
