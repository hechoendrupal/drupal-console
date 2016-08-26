<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\EntitiesCommand.
 */

namespace Drupal\Console\Command\Update;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Utility\Error;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Core\State\State;
use Drupal\Core\Entity\EntityDefinitionUpdateManager;

/**
 * Class EntitiesCommand.
 *
 * @package Drupal\Console\Command\Update
 */
class EntitiesCommand extends Command
{
    use CommandTrait;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var EntityDefinitionUpdateManager
     */
    protected $entityDefinitionUpdateManager;

    /**
     * EntitiesCommand constructor.
     * @param State                         $state
     * @param EntityDefinitionUpdateManager $entityDefinitionUpdateManager
     */
    public function __construct(
        State $state,
        EntityDefinitionUpdateManager $entityDefinitionUpdateManager
    ) {
        $this->state = $state;
        $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('update:entities')
            ->setDescription($this->trans('commands.update.entities.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        //$state = $this->getDrupalService('state');
        $io->info($this->trans('commands.site.maintenance.messages.maintenance-on'));
        $io->info($this->trans('commands.update.entities.messages.start'));
        $this->state->set('system.maintenance_mode', true);

        try {
            $this->entityDefinitionUpdateManager->applyUpdates();
            /* @var Drupal\Core\Entity\EntityStorageException $e */
        } catch (EntityStorageException $e) {
            /* @var Drupal\Core\Utility\Error $variables */
            $variables = Error::decodeException($e);
            $io->info($this->trans('commands.update.entities.messages.error'));
            $io->info($variables);
        }

        $this->state->set('system.maintenance_mode', false);
        $io->info($this->trans('commands.update.entities.messages.end'));
        $this->get('chain_queue')->addCommand('cache:rebuild', ['cache' => 'all']);
        $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));
    }
}
