<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ContainerDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouterDebugCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('router:debug')
      ->setDescription('Displays current routes for an application')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $routes = $this->getRoutes();

    $table = $this->getHelperSet()->get('table');
    $table->setHeaders(['Name', 'Path']);
    $table->setlayout($table::LAYOUT_COMPACT);

    foreach ($routes as $route_name => $route) {
      $table->addRow([$route_name, $route->getPath()]);
    }
    $table->render($output);
  }
}
