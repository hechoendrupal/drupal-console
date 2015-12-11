<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ControllerCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Generator\ControllerGenerator;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class ControllerCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:controller')
            ->setDescription($this->trans('commands.generate.controller.description'))
            ->setHelp($this->trans('commands.generate.controller.command.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.controller.options.class')
            )
            ->addOption(
                'title',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.controller.options.title')
            )
            ->addOption(
                'method',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.controller.options.method')
            )
            ->addOption(
                'route',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.controller.options.route')
            )
            ->addOption('services', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $this->trans('commands.common.options.services'))
            ->addOption('test', '', InputOption::VALUE_NONE, $this->trans('commands.generate.controller.options.test'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $controller_title = is_array($input->getOption('title'))?$input->getOption('title'): array($input->getOption('title'));
        $method_name = is_array($input->getOption('method'))?$input->getOption('method'): array($input->getOption('method'));
        $route = is_array($input->getOption('route'))?$input->getOption('route'): array($input->getOption('route'));
        $test = $input->getOption('test');
        $services = $input->getOption('services');

        // Combine all routes
        $numberOfRoutes = count($controller_title);
        $routes = [];
        for ($i=0; $i < $numberOfRoutes; $i++) {
            $routes[$i]['title'] = $controller_title[$i];
            $routes[$i]['method'] = $method_name[$i];
            $routes[$i]['route'] = (strpos($route[$i], '/') === 0) ? $route[$i] : '/' . $route[$i] ;
        }

        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

        // @see use Drupal\Console\Command\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        // Controller machine name
        $class_machine_name = $this->getStringHelper()->camelCaseToMachineName($class_name);

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $class_name, $routes, $test, $build_services, $class_machine_name);

        $this->getChain()->addCommand('router:rebuild');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $output->ask(
                $this->trans('commands.generate.controller.questions.class'),
                'DefaultController',
                function ($class_name) {
                    return $this->validateClassName($class_name);
                }
            );
            $input->setOption('class', $class_name);
        }

        $routes = [];
        while (true) {
            // --title option
            $title = $input->getOption('title');
            if (!$title) {
                $title = $output->ask(
                    $this->trans('commands.generate.controller.questions.title'),
                    '',
                    function ($title) use ($routes) {
                        if (!empty($routes) && empty($title)) {
                            return false;
                        }

                        if (in_array($title, array_column($routes, 'title'))) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    $this->trans('commands.generate.controller.messages.title-already-added'),
                                    $title
                                )
                            );
                        }

                        return $title;
                    }
                );
            }

            if ($title===false) {
                break;
            }

            // --method option
            $method = $input->getOption('method');
            if (!$method) {
                $method = $output->ask(
                    $this->trans('commands.generate.controller.questions.method'),
                    'index',
                    function ($method) use ($routes) {
                        if (in_array($method, array_column($routes, 'method'))) {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.generate.controller.messages.method-already-added'), $title)
                            );
                        }

                        return $method;
                    }
                );
            }

            // --route option option
            $route = $input->getOption('route');
            if (!$route) {
                $route = $output->ask(
                    $this->trans('commands.generate.controller.questions.route'),
                    sprintf('%s/%s/hello/{name}', $module, $method),
                    function ($route) use ($routes) {
                        if (in_array($route, array_column($routes, 'route'))) {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.generate.controller.messages.route-already-added'), $new_route)
                            );
                        }

                        return $route;
                    }
                );
            }

            $routes[] = [
              'title' => $title,
              'method' => $method,
              'route' => $route
            ];
        }

        $input->setOption('title', array_column($routes, 'title'));
        $input->setOption('method', array_column($routes, 'method'));
        $input->setOption('route', array_column($routes, 'route'));

        // --test option
        $test = $input->getOption('test');
        if (!$test) {
            $test = $output->confirm(
                $this->trans('commands.generate.controller.questions.test'),
                true
            );

            $input->setOption('test', $test);
        }

        // --services option
        // @see use Drupal\Console\Command\ServicesTrait::servicesQuestion
        $services = $this->servicesQuestion($output);
        $input->setOption('services', $services);
    }

    /**
     * @return \Drupal\Console\Generator\ControllerGenerator
     */
    protected function createGenerator()
    {
        return new ControllerGenerator();
    }
}
