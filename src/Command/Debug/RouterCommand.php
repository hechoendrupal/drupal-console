<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\RouterCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Component\Serialization\Yaml;

class RouterCommand extends Command
{
    /**
     * @var RouteProviderInterface
     */
    protected $routeProvider;

    /**
     * DebugCommand constructor.
     *
     * @param RouteProviderInterface $routeProvider
     */
    public function __construct(RouteProviderInterface $routeProvider)
    {
        $this->routeProvider = $routeProvider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('debug:router')
            ->setDescription($this->trans('commands.debug.router.description'))
            ->addArgument(
                'route-name',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                $this->trans('commands.debug.router.arguments.route-name')
            )
            ->setAliases(['dr']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $route_name = $input->getArgument('route-name');

        if ($route_name) {
            $this->getRouteByNames($route_name);
        } else {
            $this->getAllRoutes();
        }
    }

    protected function getAllRoutes()
    {
        $routes = $this->routeProvider->getAllRoutes();

        $tableHeader = [
            $this->trans('commands.debug.router.messages.name'),
            $this->trans('commands.debug.router.messages.path'),
        ];

        $tableRows = [];
        foreach ($routes as $route_name => $route) {
            $tableRows[] = [$route_name, $route->getPath()];
        }

        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }

    protected function getRouteByNames($route_name)
    {
        $routes = $this->routeProvider->getRoutesByNames($route_name);

        foreach ($routes as $name => $route) {
            $tableHeader = [
                $this->trans('commands.debug.router.messages.route'),
                '<info>'.$name.'</info>'
            ];
            $tableRows = [];

            $tableRows[] = [
                '<comment>'.$this->trans('commands.debug.router.messages.path').'</comment>',
                $route->getPath(),
            ];

            $tableRows[] = ['<comment>'.$this->trans('commands.debug.router.messages.defaults').'</comment>'];
            $attributes = $this->addRouteAttributes($route->getDefaults());
            foreach ($attributes as $attribute) {
                $tableRows[] = $attribute;
            }

            $tableRows[] = ['<comment>'.$this->trans('commands.debug.router.messages.requirements').'</comment>'];
            $requirements = $this->addRouteAttributes($route->getRequirements());
            foreach ($requirements as $requirement) {
                $tableRows[] = $requirement;
            }

            $tableRows[] = ['<comment>'.$this->trans('commands.debug.router.messages.options').'</comment>'];
            $options = $this->addRouteAttributes($route->getOptions());
            foreach ($options as $option) {
                $tableRows[] = $option;
            }

            $this->getIo()->table($tableHeader, $tableRows, 'compact');
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
