<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RouterDebugCommand.
 */

namespace Drupal\Console\Command\Router;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Component\Serialization\Yaml;

class DebugCommand extends ContainerAwareCommand
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
        $io = new DrupalStyle($input, $output);

        $route_name = $input->getArgument('route-name');

        if ($route_name) {
            $this->getRouteByNames($io, $route_name);
        } else {
            $this->getAllRoutes($io);
        }
    }

    protected function getAllRoutes(DrupalStyle $io)
    {
        $routeProvider = $this->getRouteProvider();
        $routes = $routeProvider->getAllRoutes();

        $tableHeader = [
            $this->trans('commands.router.debug.messages.name'),
            $this->trans('commands.router.debug.messages.path'),
        ];

        $tableRows = [];
        foreach ($routes as $route_name => $route) {
            $tableRows[] = [$route_name, $route->getPath()];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }

    protected function getRouteByNames(DrupalStyle $io, $route_name)
    {
        $rp = $this->getRouteProvider();
        $routes = $rp->getRoutesByNames($route_name);

        foreach ($routes as $name => $route) {
            $tableHeader = [
                $this->trans('commands.router.debug.messages.route'),
                '<info>'.$name.'</info>'
            ];
            $tableRows = [];

            $tableRows[] = [
                '<comment>'.$this->trans('commands.router.debug.messages.path').'</comment>',
                $route->getPath(),
            ];

            $tableRows[] = ['<comment>'.$this->trans('commands.router.debug.messages.defaults').'</comment>'];
            $attributes = $this->addRouteAttributes($route->getDefaults());
            foreach ($attributes as $attribute) {
                $tableRows[] = $attribute;
            }

            $tableRows[] = ['<comment>'.$this->trans('commands.router.debug.messages.options').'</comment>'];
            $options = $this->addRouteAttributes($route->getOptions());
            foreach ($options as $option) {
                $tableRows[] = $option;
            }

            $io->table($tableHeader, $tableRows, 'compact');
        }
    }

    protected function addRouteAttributes($attr, $attributes = null)
    {
        foreach ($attr as $key => $value) {
            if (is_array($value)) {
                $attributes[] = [
                  ' '.$key,
                  str_replace(
                      '- ',
                      '',
                      Yaml::encode($value)
                  )
                ];
            } else {
                $attributes[] = [' '.$key, $value];
            }
        }

        return $attributes;
    }
}
