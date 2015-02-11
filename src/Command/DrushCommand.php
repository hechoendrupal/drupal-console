<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\DrushCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DrushCommand extends ContainerAwareCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('drush')
      ->setDescription($this->trans('commands.drush.description'))
      ->addArgument('args', InputArgument::IS_ARRAY, $this->trans('commands.drush.arguments.args'))
      ->setHelp($this->trans('commands.drush.help'))
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $args = '';
    if ($arguments = $input->getArgument('args')) {
      $args .= ' '.implode(' ', $arguments);
      $c_args = preg_replace('/[^a-z0-9-= ]/i', '', $args);
    }

    if (`which drush`) {
      system('drush'.$c_args);
    } else {
      $output->write("<error>".$this->trans('commands.drush.message.not_found')."</error>");
    }
  }
}
