<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\CronCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class CronCommand extends Command
{
    use CommandTrait;

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
        $io = new DrupalStyle($input, $output);

        $io->section(
            $this->trans('commands.debug.cron.messages.module-list')
        );

        $io->table(
            [ $this->trans('commands.debug.cron.messages.module') ],
            $this->moduleHandler->getImplementations('cron'),
            'compact'
        );
    }
}
