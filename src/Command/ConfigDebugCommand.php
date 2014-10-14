<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
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
      ->addArgument('config-name', InputArgument::OPTIONAL, 'Config name')
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $config_name = $input->getArgument('config-name');

    $container = $this->getContainer();
    $configFactory = $container->get('config.factory');

    $table = $this->getHelperSet()->get('table');
    $table->setlayout($table::LAYOUT_COMPACT);

    $this->getAllConfigurations($output, $table, $configFactory);

  }

  /**
   * @param $output
   * @param $table
   * @param $configFactory
     */
  private function getAllConfigurations($output, $table, $configFactory){
    $names = $configFactory->listAll();
    $table->setHeaders(['Name']);
    foreach ($names as $name) {
      $table->addRow([$name]);
    }
    $table->render($output);
  }
}
