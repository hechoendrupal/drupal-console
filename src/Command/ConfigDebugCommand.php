<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigDebugCommand extends ContainerAwareCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('config:debug')
      ->setDescription('Show the current configuration')
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $container = $this->getContainer();
    $configFactory = $container->get('config.factory');

    $table = $this->getHelperSet()->get('table');
    $table->setlayout($table::LAYOUT_COMPACT);

    $names = $configFactory->listAll();
    $table->setHeaders(['Name']);
    foreach ($names as $name) {
      $table->addRow([$name]);
    }
    $table->render($output);

  }
}
