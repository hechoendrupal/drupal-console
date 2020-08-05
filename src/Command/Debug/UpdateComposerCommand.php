<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\UpdateComposerCommand.
 */

namespace Drupal\Console\Command\Debug;

use Drupal\Console\Command\Shared\UpdateTrait;
use Drupal\Console\Core\Utils\DrupalFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Process\Process;

class UpdateComposerCommand extends Command
{
    use UpdateTrait;

    /**
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * DebugComposerCommand constructor.
     *
     * @param DrupalFinder $drupalFinder
     */
    public function __construct(DrupalFinder $drupalFinder) {
        $this->drupalFinder = $drupalFinder;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('debug:update:composer')
            ->setDescription($this->trans('commands.debug.update.composer.description'))
            ->addOption(
                'only-drupal',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.debug.update.composer.options.only-drupal')
            )
            ->setAliases(['duc']);
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $onlyDrupal = $input->getOption('only-drupal');

        $process = new Process("composer show --outdated --format=json");
        $process->setTimeout(null);
        $process->setWorkingDirectory($this->drupalFinder->getComposerRoot());
        $process->run();

        if($process->isSuccessful()){
            $jsonData = json_decode($process->getOutput());
            $this->showComposerUpdateTable($jsonData->installed, $onlyDrupal, $this->trans('commands.debug.update.composer.messages.composer-list'));
        }
    }
}
