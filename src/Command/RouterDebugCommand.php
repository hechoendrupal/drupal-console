<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\RouterDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;

class RouterDebugCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('router:debug')
      ->setDescription('Displays current routes for an application')
      ->addArgument('route-name', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Route names')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $route_name = $input->getArgument('route-name');
    $table = $this->getHelperSet()->get('table');
    if ($route_name) {
      $this->getRouteByNames($route_name, $output, $table);
    }
    else {
      $this->getAllRoutes($output, $table);
    }
  }

  protected function getAllRoutes($output, $table)
  {
    $rp = $this->getRouteProvider();
    $routes  = $rp->getAllRoutes();

    $table->setHeaders(['Name', 'Path']);
    foreach ($routes as $route_name => $route) {
      $table->addRow([$route_name, $route->getPath()]);
    }
    $table->render($output);
  }

  protected function getRouteByNames($route_name, $output, $table)
  {
    $rp = $this->getRouteProvider();
    $routes = $rp->getRoutesByNames($route_name);
    $table->setHeaders(['Route name', 'Options']);
    $table->setlayout(TableHelper::LAYOUT_BORDERLESS);

    $rows = [];
    foreach ($routes as $name => $route) {
      $table->addRow(['<info>'.$name.'</info>']);
      $table->addRow([' <comment>+ Pattern</comment>', $route->getPath()]);

      $table->addRow([' <comment>+ Defaults</comment>']);
      $table = $this->addRouteAttributes($route->getDefaults(), $table);

      $table->addRow([' <comment>+ Options</comment>']);
      $table = $this->addRouteAttributes($route->getOptions(), $table);
    }
    $table->render($output);
  }

  protected function addRouteAttributes($attr, $table)
  {
    foreach ($attr as $key => $value) {
      if (is_array($value)) {
        $table= $this->addRouteAttributes($value, $table);
      }
      else {
        $table->addRow(['  <comment>- </comment>'.$key, $value]);
      }
    }
    return $table;
  }
}
