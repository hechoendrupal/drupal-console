<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\CronCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;

class CronCommand extends Command
{
    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * CronCommand constructor.
     *
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
            ->setName('debug:cron')
            ->setDescription($this->trans('commands.debug.cron.description'))
            ->setAliases(['dcr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getIo()->section(
            $this->trans('commands.debug.cron.messages.module-list')
        );

        $this->getIo()->table(
            [ $this->trans('commands.debug.cron.messages.module') ],
            $this->moduleHandler->getImplementations('cron'),
            'compact'
        );
    }
}
