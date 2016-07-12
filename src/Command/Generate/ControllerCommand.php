<?php

/**
 * @file
 * Contains Drupal\Console\Command\Generate\ControllerCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\InputTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\ControllerGenerator;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class ControllerCommand extends GeneratorCommand
{
    use ModuleTrait;
    use ServicesTrait;
    use ConfirmationTrait;
    use InputTrait;

    protected function configure()
    {
        $this
            ->setName('generate:controller')
            ->setDescription($this->trans('commands.generate.controller.description'))
            ->setHelp($this->trans('commands.generate.controller.help'))
            ->addOption(
                'module',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.controller.options.class')
            )
            ->addOption(
                'routes',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.controller.options.routes')
            )
            ->addOption(
                'services',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->addOption(
                'test',
                '',
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.controller.options.test')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;
        $module = $input->getOption('module');
        $class = $input->getOption('class');
        $routes = $input->getOption('routes');
        $test = $input->getOption('test');
        $services = $input->getOption('services');

        $routes = $this->inlineValueAsArray($routes);
        $input->setOption('routes', $routes);

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $class, $routes, $test, $build_services);

        $this->getChain()->addCommand('router:rebuild');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $io->ask(
                $this->trans('commands.generate.controller.questions.class'),
                'DefaultController',
                function ($class) {
                    return $this->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        $routes = $input->getOption('routes');
        if (!$routes) {
            while (true) {
                $title = $io->askEmpty(
                    $this->trans('commands.generate.controller.questions.title'),
                    function ($title) use ($routes) {
                        if ($routes && empty(trim($title))) {
                            return false;
                        }

                        if (!$routes && empty(trim($title))) {
                            throw new \InvalidArgumentException(
                                $this->trans(
                                    'commands.generate.controller.messages.title-empty'
                                )
                            );
                        }

                        if (in_array($title, array_column($routes, 'title'))) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    $this->trans(
                                        'commands.generate.controller.messages.title-already-added'
                                    ),
                                    $title
                                )
                            );
                        }

                        return $title;
                    }
                );

                if ($title === '') {
                    break;
                }

                $method = $io->ask(
                    $this->trans('commands.generate.controller.questions.method'),
                    'hello',
                    function ($method) use ($routes) {
                        if (in_array($method, array_column($routes, 'method'))) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    $this->trans(
                                        'commands.generate.controller.messages.method-already-added'
                                    ),
                                    $method
                                )
                            );
                        }

                        return $method;
                    }
                );
                
                $path = $io->ask(
                    $this->trans('commands.generate.controller.questions.path'),
                    sprintf('/%s/hello/{name}', $module),
                    function ($path) use ($routes) {
                        $routeProvider = $this->getRouteProvider();
                        if (count($routeProvider->getRoutesByPattern($path)) > 0
                            || in_array($path, array_column($routes, 'path'))
                        ) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    $this->trans(
                                        'commands.generate.controller.messages.path-already-added'
                                    ),
                                    $path
                                )
                            );
                        }

                        return $path;
                    }
                );
                $classMachineName = $this->getStringHelper()->camelCaseToMachineName($class);
                $routeName = $module . '.' . $classMachineName . '_' . $method;
                $routeProvider = $this->getRouteProvider();
                if ($routeProvider->getRoutesByNames([$routeName])
                    || in_array($routeName, $routes)
                ) {
                    $routeName .= '_' . rand(0, 100);
                }
                
                $routes[] = [
                    'title' => $title,
                    'name' => $routeName,
                    'method' => $method,
                    'path' => $path
                ];
            }
            $input->setOption('routes', $routes);
        }

        // --test option
        $test = $input->getOption('test');
        if (!$test) {
            $test = $io->confirm(
                $this->trans('commands.generate.controller.questions.test'),
                true
            );

            $input->setOption('test', $test);
        }

        // --services option
        // @see use Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
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
