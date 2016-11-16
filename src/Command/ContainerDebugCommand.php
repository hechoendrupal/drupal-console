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
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ContainerDebugCommand
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

        if ($parameters) {
            $parameterList = $this->getParameterList();
            ksort($parameterList);
            $io->write(Yaml::dump(['parameters' => $parameterList], 4, 2));

            return 0;
        }

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

        return 0;
    }

    private function getServiceList()
    {
        $services = [];
        $serviceDefinitions = $this->container
            ->getParameter('console.service_definitions');

        foreach ($serviceDefinitions as $serviceId => $serviceDefinition) {
            $services[] = [$serviceId, $serviceDefinition->getClass()];
        }
        return $services;
    }

    private function getServiceDetail($service)
    {
        $serviceInstance = $this->get($service);
        $serviceDetail = [];

        if ($serviceInstance) {
            $serviceDetail[] = [
                $this->trans('commands.container.debug.messages.service'),
                $service
            ];
            $serviceDetail[] = [
                $this->trans('commands.container.debug.messages.class'),
                get_class($serviceInstance)
            ];
            $serviceDetail[] = [
                $this->trans('commands.container.debug.messages.interface'),
                Yaml::dump(class_implements($serviceInstance))
            ];
            if ($parent = get_parent_class($serviceInstance)) {
                $serviceDetail[] = [
                    $this->trans('commands.container.debug.messages.parent'),
                    $parent
                ];
            }
            if ($vars = get_class_vars($serviceInstance)) {
                $serviceDetail[] = [
                    $this->trans('commands.container.debug.messages.variables'),
                    Yaml::dump($vars)
                ];
            }
            if ($methods = get_class_methods($serviceInstance)) {
                $serviceDetail[] = [
                    $this->trans('commands.container.debug.messages.methods'),
                    Yaml::dump($methods)
                ];
            }
        }

        return $serviceDetail;
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
