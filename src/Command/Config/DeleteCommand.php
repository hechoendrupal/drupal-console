<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DeleteCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class DeleteCommand extends ContainerAwareCommand {
  protected $allConfig = [];
  protected $configFactory = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:delete')
      ->setDescription($this->trans('commands.config.delete.description'))
      ->addArgument(
        'name',
        InputArgument::OPTIONAL,
        $this->trans('commands.config.delete.arguments.name')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    // Init Drupal style and retrieve name argument.
    $io = new DrupalStyle($input, $output);
    $name = $input->getArgument('name');
    // Check config name is not missing.
    if (!$name) {
      // Define choice list.
      $name = $io->choiceNoList(
        $this->trans('commands.config.delete.arguments.name'),
        $this->getAllConfigNames(),
        'all'
      );
      $input->setArgument('name', $name);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Init Drupal style and retrieve name argument.
    $io = new DrupalStyle($input, $output);
    $name = $input->getArgument('name');
    // Check config name is not missing.
    if (!$name) {
      $io->error($this->trans('commands.config.delete.messages.name'));
      return 1;
    }

    // Check if current option chose was "all".
    if ('all' === $name) {
      // Caveat about remove all configuration.
      $io->caution($this->trans('commands.config.delete.warnings.undo'));
      // Double check before execute it.
      if ($io->confirm($this->trans('commands.config.delete.questions.sure'))) {
        // Remove all configuration.
        foreach ($this->yieldAllConfig() as $name) {
          $this->removeConfig($io, $name, FALSE);
        }
        // Define successful message.
        $io->success($this->trans('commands.config.delete.messages.all'));
      }
    } // Load $configStorage and check config name already exists.
    elseif (($configStorage = $this->getService('config.storage')) && ($configStorage->exists($name))) {
      $this->removeConfig($io, $name);
    }
    else {
      // Otherwise, shows up error because config name does not exist.
      $message = sprintf($this->trans('commands.config.delete.messages.not-exists'), $name);
      $io->error($message);
      return 1;
    }
  }

  /**
   * Retrieve config factory property.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  private function configFactory() {
    // Define config factory from service if it does not exist.
    $this->configFactory = $this->configFactory ?: $this->getConfigFactory();
    return $this->configFactory;
  }

  /**
   * Retrieve configuration names form cache or service factory.
   *
   * @return array
   *   All configuration names.
   */
  private function getAllConfigNames() {
    // If configuration names exist, then return them.
    if (!empty($this->allConfig)) {
      return $this->allConfig;
    }
    // Retrieve configuration factory.
    foreach ($this->configFactory()->listAll() as $name) {
      // Store configuration name.
      $this->allConfig[] = $name;
    }
    // Return all configuration names.
    return $this->allConfig;
  }

  /**
   * Yield configuration names.
   *
   * @return \Generator
   *   Yield generator with config name.
   */
  private function yieldAllConfig() {
    // Be sure $allConfig property already exists.
    $this->allConfig = $this->allConfig ?: $this->getAllConfigNames();
    // Walk trough all config names and yield them.
    foreach ($this->allConfig as $name) {
      yield $name;
    }
  }

  /**
   * Delete given config name.
   *
   * @param DrupalStyle $io IO instance.
   * @param String $name Given config name.
   * @param bool $show_message Flag to show message or not.
   * @return int
   *   When exception was threw.
   */
  private function removeConfig(DrupalStyle $io, $name, $show_message = TRUE) {
    try {
      // Retrieve config factory and delete given configuration.
      $this->configFactory()->getEditable($name)->delete();
    } catch (\Exception $e) {
      // Show error message.
      $io->error($e->getMessage());
      return 1;
    }

    // Check flag to show message.
    if ($show_message) {
      // Define and print successful message.
      $message = sprintf($this->trans('commands.config.delete.messages.deleted'), $name);
      $io->success($message);
    }
  }
}
