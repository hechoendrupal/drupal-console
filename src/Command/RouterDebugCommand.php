<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RouterDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouterDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('router:debug')
          ->setDescription($this->trans('commands.router.debug.description'))
          ->addArgument(
              'route-name',
              InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
              $this->trans('commands.router.debug.arguments.route-name')
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $route_name = $input->getArgument('route-name');
        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);
        if ($route_name) {
            $this->getRouteByNames($route_name, $output, $table);
        } else {
            $this->getAllRoutes($output, $table);
        }
    }

    protected function getAllRoutes($output, $table)
    {
        $rp = $this->getRouteProvider();
        $routes = $rp->getAllRoutes();

        $table->setHeaders(
            [
            $this->trans('commands.router.debug.messages.name'),
            $this->trans('commands.router.debug.messages.path'),
            ]
        );
        $table->setlayout($table::LAYOUT_COMPACT);
        foreach ($routes as $route_name => $route) {
            $table->addRow([$route_name, $route->getPath()]);
        }
        $table->render($output);
    }

    protected function getRouteByNames($route_name, $output, $table)
    {
        $rp = $this->getRouteProvider();
        $routes = $rp->getRoutesByNames($route_name);
        $table->setHeaders(
            [
            $this->trans('commands.router.debug.messages.name'),
            $this->trans('commands.router.debug.messages.options'),
            ]
        );
        $table->setlayout($table::LAYOUT_COMPACT);

        foreach ($routes as $name => $route) {
            $table->addRow(['<info>'.$name.'</info>']);
            $table->addRow([
              ' <comment>+ '.$this->trans('commands.router.debug.messages.pattern').'</comment>',
              $route->getPath(),
            ]);

            $table->addRow([' <comment>+ '.$this->trans('commands.router.debug.messages.defaults').'</comment>']);
            $table = $this->addRouteAttributes($route->getDefaults(), $table);

            $table->addRow([' <comment>+ '.$this->trans('commands.router.debug.messages.options').'</comment>']);
            $table = $this->addRouteAttributes($route->getOptions(), $table);
        }
        $table->render($output);
    }

    protected function addRouteAttributes($attr, $table)
    {
        foreach ($attr as $key => $value) {
            if (is_array($value)) {
                $table = $this->addRouteAttributes($value, $table);
            } else {
                $table->addRow(['  <comment>- </comment>'.$key, $value]);
            }
        }

        return $table;
    }
}
