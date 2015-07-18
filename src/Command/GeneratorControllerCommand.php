<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\GeneratorControllerCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ServicesTrait;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Generator\ControllerGenerator;

class GeneratorControllerCommand extends GeneratorCommand
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
              'class-name',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.controller.options.class-name')
          )
          ->addOption(
              'controller-title',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.controller.options.controller-title')
          )
          ->addOption(
              'method-name',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.generate.controller.options.method-name')
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
        $dialog = $this->getDialogHelper();

        if ($this->confirmationQuestion($input, $output, $dialog)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class-name');
        $controller_title = is_array($input->getOption('controller-title'))?$input->getOption('controller-title'): array($input->getOption('controller-title'));
        $method_name = is_array($input->getOption('method-name'))?$input->getOption('method-name'): array($input->getOption('method-name'));
        $route = is_array($input->getOption('route'))?$input->getOption('route'): array($input->getOption('route'));
        $test = $input->getOption('test');
        $services = $input->getOption('services');

        // Combine all routes
        $routes = array();
        for($i=0; $i < count($controller_title); $i++) {
            $routes[$i]['title'] = $controller_title[$i];
            $routes[$i]['method'] = $method_name[$i];
            $routes[$i]['route'] = (strpos($route[$i], '/') === 0) ? $route[$i] : '/' . $route[$i] ;
        }



        $learning = false;
        if ($input->hasOption('learning')) {
            $learning = $input->getOption('learning');
        }

        // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        // Controller machine name
        $class_machine_name = $this->getStringUtils()->camelCaseToMachineName($class_name);

        $generator = $this->getGenerator();
        $generator->setLearning($learning);
        $generator->generate($module, $class_name, $routes, $test, $build_services, $class_machine_name);

        $this->getHelper('chain')->addCommand('router:rebuild');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        // --class-name option
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.controller.questions.class-name'), 'DefaultController'),
              function ($class_name) {
                  return $this->validateClassName($class_name);
              },
              false,
              'DefaultController',
              null
            );
        }
        $input->setOption('class-name', $class_name);
        $i =0;
        $routers = array();
        while (true) {
            // --controller-title option
            $controller_title = $input->getOption('controller-title');
            if (!$controller_title) {

                $controller_title = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion((count($routers) < 1 ? $this->trans('commands.generate.controller.questions.controller-title') : $this->trans('commands.generate.controller.questions.other-controller-title')) , ''),
                  function ($title) use($routers) {
                      if (count($routers) < 1 && empty($title)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.controller.messages.invalid-controller-title'), $title)
                          );
                      } elseif (in_array($title, array_column($routers, 'title'))) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.controller.messages.title-already-added'), $title)
                          );
                      } else {
                          return $title;
                      }

                  },
                  false,
                  '',
                  null
                );
            }

            if (empty($controller_title)) {
                break;
            }

            $routers[$i]['title'] = $controller_title;

            // --method-name option
            $method_name = $input->getOption('method-name');
            if (!$method_name) {
                $method_name = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion($this->trans('commands.generate.controller.questions.method-name'), 'index'),
                  function ($method) use($routers) {
                      if (empty($method)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.controller.messages.invalid-method-name'), $method)
                          );
                      } elseif (in_array($method, array_column($routers, 'method'))) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.controller.messages.method-name-already-added'), $title)
                          );
                      } else {
                          return $method;
                      }
                  },
                  false,
                  'index',
                  null
                );
            }
            $routers[$i]['method'] = $method_name;

            // --route option option
            $route = $input->getOption('route');
            if (!$route) {
                $route = $dialog->askAndValidate(
                  $output,
                  $dialog->getQuestion(
                    $this->trans('commands.generate.controller.questions.route'),
                    $module . '/' . $method_name . '/{param_1}/{param_2}'
                  ),
                  function ($new_route) use($routers) {
                      if (empty($new_route)) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.controller.messages.invalid-route'), $new_route)
                          );
                      } elseif (in_array($new_route, array_column($routers, 'route'))) {
                          throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.generate.controller.messages.route-already-added'), $new_route)
                          );
                      } else {
                          return $new_route;
                      }
                  },
                  false,
                  $module . '/' . $method_name . '/{param_1}/{param_2}',
                  null
                );
            }
            $routers[$i]['route'] = $route;
            $i++;
        }

        $input->setOption('controller-title', array_column($routers, 'title'));
        $input->setOption('method-name', array_column($routers, 'method'));
        $input->setOption('route', array_column($routers, 'route'));

        // --test option
        $test = $input->getOption('test');
        if (!$test && $dialog->askConfirmation(
            $output,
            $dialog->getQuestion($this->trans('commands.generate.controller.questions.test'), 'yes', '?'),
            true
        )
        ) {
            $test = true;
        }
        $input->setOption('test', $test);

        // --services option
        // @see use Drupal\AppConsole\Command\Helper\ServicesTrait::servicesQuestion
        $services_collection = $this->servicesQuestion($output, $dialog);
        $input->setOption('services', $services_collection);
    }

    /**
     * @return \Drupal\AppConsole\Generator\ControllerGenerator
     */
    protected function createGenerator()
    {
        return new ControllerGenerator();
    }
}
