<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Update\EntitiesCommand.
 */

namespace Drupal\Console\Command\Update;

use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Utility\Error;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class EntitiesCommand.
 *
 * @package Drupal\Console\Command\Update
 */
class EntitiesCommand extends ContainerAwareCommand
{
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

        $state = $this->getService('state');
        $io->info($this->trans('commands.site.maintenance.messages.maintenance-on'));
        $io->info($this->trans('commands.update.entities.messages.start'));
        $state->set('system.maintenance_mode', true);

        try {
            $this->getService('entity.definition_update_manager')->applyUpdates();
        } catch (EntityStorageException $e) {
            $variables = Error::decodeException($e);
            $io->info($this->trans('commands.update.entities.messages.error'));
            $io->info($variables);
        }

        $state->set('system.maintenance_mode', false);
        $io->info($this->trans('commands.update.entities.messages.end'));
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
        $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));
    }
}
