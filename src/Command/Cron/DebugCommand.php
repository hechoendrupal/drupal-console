<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cron\DebugCommand.
 */

namespace Drupal\Console\Command\Cron;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * DebugCommand constructor.
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(ModuleHandlerInterface $moduleHandler)
    {
        $this->moduleHandler = $moduleHandler;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cron:debug')
            ->setDescription($this->trans('commands.cron.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $io->section(
            $this->trans('commands.cron.debug.messages.module-list')
        );

        $io->table(
            [
                $this->trans('commands.cron.debug.messages.module')
            ],
            $this->moduleHandler->getImplementations('cron'),
            'compact'
        );
    }
}
