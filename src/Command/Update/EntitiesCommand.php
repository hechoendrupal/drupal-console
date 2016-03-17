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
 * Class EntityUpdatesCommand.
 *
 * @package Drupal\entityupdates
 */
class EntitiesCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('update:entities')
      ->setDescription($this->trans('command.update.entities.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $io->info($this->trans('command.update.entities.messages.start'));
    $state = $this->getService('state');
    $state->set('system.maintenance_mode', TRUE);

    try {
      \Drupal::entityDefinitionUpdateManager()->applyUpdates();
    } catch (EntityStorageException $e) {
      $variables = Error::decodeException($e);
      $io->info($this->trans('command.update.entities.messages.error'));
      $io->info($variables);
    }

    $state->set('system.maintenance_mode', FALSE);
    $io->info($this->trans('commands.site.maintenance.messages.maintenance-off'));
    $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    $io->info($this->trans('command.update.entities.messages.end'));
  }
}
