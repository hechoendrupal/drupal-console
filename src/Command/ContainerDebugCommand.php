<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ContainerDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ContainerDebugCommand
 *
 * @package Drupal\Console\Command
 */
class ContainerDebugCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('container:debug')
            ->setDescription($this->trans('commands.container.debug.description'))
            ->addOption(
                'parameters',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.container.debug.arguments.service')
            )
            ->addArgument(
                'service',
                InputArgument::OPTIONAL,
                $this->trans('commands.container.debug.arguments.service')
            )->addArgument(
                'method',
                InputArgument::OPTIONAL,
                $this->trans('commands.container.debug.arguments.method')
            )->addArgument(
                'arguments',
                InputArgument::OPTIONAL,
                $this->trans('commands.container.debug.arguments.arguments')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $service = $input->getArgument('service');
        $parameters = $input->getOption('parameters');
        $method = $input->getArgument('method');
        $args = $input->getArgument('arguments');

        if ($parameters) {
            $parameterList = $this->getParameterList();
            ksort($parameterList);
            $io->write(Yaml::dump(['parameters' => $parameterList], 4, 2));

            return 0;
        }

        if ($method) {
            $tableHeader = [];
            $callbackRow = $this->getCallbackReturnList($service, $method, $args);
            $io->table($tableHeader, $callbackRow, 'compact');

            return 0;
        } else {

            $tableHeader = [];
            if ($service) {
                $tableRows = $this->getServiceDetail($service);
                $io->table($tableHeader, $tableRows, 'compact');

                return 0;
            }

            $tableHeader = [
                $this->trans('commands.container.debug.messages.service_id'),
                $this->trans('commands.container.debug.messages.class_name')
            ];

            $tableRows = $this->getServiceList();
            $io->table($tableHeader, $tableRows, 'compact');
        }

        return 0;
    }

    private function getCallbackReturnList($service, $method, $args) {

        if ($args != NULL) {
            $parsedArgs = json_decode($args, TRUE);
            if (!is_array($parsedArgs)) $parsedArgs = explode(",", $args);
        } else {
            $parsedArgs = NULL;
        }
        $serviceInstance = \Drupal::service($service);

        if (!method_exists($serviceInstance, $method)) {
            throw new \Symfony\Component\DependencyInjection\Exception\BadMethodCallException($this->trans('commands.container.debug.errors.method_not_exists'));

            return $serviceDetail;
        }
        $serviceDetail[] = [
            '<fg=green>'.$this->trans('commands.container.debug.messages.service').'</>',
            '<fg=yellow>'.$service.'</>'
        ];
        $serviceDetail[] = [
            '<fg=green>'.$this->trans('commands.container.debug.messages.class').'</>',
            '<fg=yellow>'.get_class($serviceInstance).'</>'
        ];
        $methods = array($method);
        $this->extendArgumentList($serviceInstance, $methods);
        $serviceDetail[] = [
            '<fg=green>'.$this->trans('commands.container.debug.messages.method').'</>',
            '<fg=yellow>'.$methods[0].'</>'
        ];
        if ($parsedArgs) {
            $serviceDetail[] = [
                '<fg=green>'.$this->trans('commands.container.debug.messages.arguments').'</>',
                json_encode($parsedArgs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
            ];
        }
        $return = call_user_func_array(array($serviceInstance,$method), $parsedArgs);
        $serviceDetail[] = [
            '<fg=green>'.$this->trans('commands.container.debug.messages.return').'</>',
            json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ];
        return $serviceDetail;
    }
    private function getServiceList()
    {
        $services = [];
        $serviceDefinitions = $this->container
            ->getParameter('console.service_definitions');

        foreach ($serviceDefinitions as $serviceId => $serviceDefinition) {
            $services[] = [$serviceId, $serviceDefinition->getClass()];
        }
        usort($services, array($this, 'compareService'));
        return $services;
    }
    private function compareService($a, $b) {
        return strcmp($a[0], $b[0]);
    }

    private function getServiceDetail($service)
    {
        $serviceInstance = $this->get($service);
        $serviceDetail = [];

        if ($serviceInstance) {
            $serviceDetail[] = [
                '<fg=green>'.$this->trans('commands.container.debug.messages.service').'</>',
                '<fg=yellow>'.$service.'</>'
            ];
            $serviceDetail[] = [
                '<fg=green>'.$this->trans('commands.container.debug.messages.class').'</>',
                '<fg=yellow>'.get_class($serviceInstance).'</>'
            ];
            $interface = str_replace("{  }", "", Yaml::dump(class_implements($serviceInstance)));
            if (!empty($interface)) {
                $serviceDetail[] = [
                    '<fg=green>'.$this->trans('commands.container.debug.messages.interface').'</>',
                    '<fg=yellow>'.$interface.'</>'
                ];
            }
            if ($parent = get_parent_class($serviceInstance)) {
                $serviceDetail[] = [
                    '<fg=green>'.$this->trans('commands.container.debug.messages.parent').'</>',
                    '<fg=yellow>'.$parent.'</>'
                ];
            }
            if ($vars = get_class_vars($serviceInstance)) {
                $serviceDetail[] = [
                    '<fg=green>'.$this->trans('commands.container.debug.messages.variables').'</>',
                    '<fg=yellow>'.Yaml::dump($vars).'</>'
                ];
            }
            if ($methods = get_class_methods($serviceInstance)) {
                sort($methods);
                $this->extendArgumentList($serviceInstance, $methods);
                $serviceDetail[] = [
                    '<fg=green>'.$this->trans('commands.container.debug.messages.methods').'</>',
                    '<fg=yellow>'.implode("\n", $methods).'</>'
                ];
            }
        } else {
            throw new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException($service);

            return $serviceDetail;
        }

        return $serviceDetail;
    }
    private function extendArgumentList($serviceInstance, &$methods) {
        foreach ($methods as $k => $m) {
            $reflection = new \ReflectionMethod($serviceInstance, $m);
            $params = $reflection->getParameters();
            $p = array();

            for ($i = 0; $i < count($params) ; $i++) {
                if ($params[$i]->isDefaultValueAvailable()) {
                    $defaultVar = $params[$i]->getDefaultValue();
                    $defaultVar = " = <fg=magenta>".str_replace(array("\n","array ("), array("", "array("), var_export($def,true)).'</>';
                } else {
                    $defaultVar = '';
                }
                if (method_exists($params[$i], 'hasType') && method_exists($params[$i], 'getType')) {
                    if ($params[$i]->hasType()) {
                        $defaultType = '<fg=white>'.strval($params[$i]->getType()).'</> ';
                    } else {
                        $defaultType = '';
                    }
                } else {
                    $defaultType = '';
                }
                if ($params[$i]->isPassedByReference()) $parameterReference = '<fg=yellow>&</>';
                else $parameterReference = '';
                $p[] = $defaultType.$parameterReference.'<fg=red>'.'$</><fg=red>'.$params[$i]->getName().'</>'.$defaultVar;
            }
            if ($reflection->isPublic()) {
                $methods[$k] = '<fg=cyan>'.$methods[$k]."</><fg=blue>(</>".implode(', ', $p)."<fg=blue>) </> ";
            }
        }
    }

    private function getParameterList()
    {
        $parameters = array_filter(
            $this->container->getParameterBag()->all(), function ($name) {
                if (preg_match('/^container\./', $name)) {
                    return false;
                }
                if (preg_match('/^drupal\./', $name)) {
                    return false;
                }
                if (preg_match('/^console\./', $name)) {
                    return false;
                }
                return true;
            }, ARRAY_FILTER_USE_KEY
        );

        return $parameters;
    }
}
