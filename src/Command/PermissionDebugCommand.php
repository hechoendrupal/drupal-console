<?php

/**
 * @file
 * Contains \Drupal\Console\Command\PermissionDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command
 */
class PermissionDebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('permission:debug')
            ->setDescription($this->trans('commands.permission.debug.description'))
            ->setHelp($this->trans('commands.permission.debug.help'))
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                $this->trans('commands.permission.debug.arguments.type')
            )
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                $this->trans('commands.permission.debug.arguments.id')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $permissionType = $input->getArgument('type');
        $permissionId = $input->getArgument('id');

        // No permission type specified, show a list of permission types.
        if (!$permissionType) {
            $tableHeader = [
                $this->trans('commands.permission.debug.table-headers.permission-type-name'),
                $this->trans('commands.permission.debug.table-headers.permission-type-class')
            ];
            $tableRows = [];
            $serviceDefinitions = $this->container
                ->getParameter('console.service_definitions');

            foreach ($serviceDefinitions as $serviceId => $serviceDefinition) {
                if (strpos($serviceId, 'permission.manager.') === 0) {
                    $serviceName = substr($serviceId, 15);
                    $tableRows[$serviceName] = [
                        $serviceName,
                        $serviceDefinition->getClass()
                    ];
                }
            }

            ksort($tableRows);
            $io->table($tableHeader, array_values($tableRows));

            return true;
        }

        $service = $this->container->get('permission.manager.' . $permissionType);
        if (!$service) {
            $io->error(
                sprintf(
                    $this->trans('commands.permission.debug.errors.permission-type-not-found'),
                    $permissionType
                )
            );
            return false;
        }

        // Valid permission type specified, no ID specified, show list of instances.
        if (!$permissionId) {
            $tableHeader = [
                $this->trans('commands.permission.debug.table-headers.permission-id'),
                $this->trans('commands.permission.debug.table-headers.permission-class')
            ];
            $tableRows = [];
            foreach ($service->getDefinitions() as $definition) {
                $permissionId = $definition['id'];
                $className = $definition['class'];
                $tableRows[$permissionId] = [$permissionId, $className];
            }
            ksort($tableRows);
            $io->table($tableHeader, array_values($tableRows));
            return true;
        }

        // Valid permission type specified, ID specified, show the definition.
        $definition = $service->getDefinition($permissionId);
        $tableHeader = [
            $this->trans('commands.permission.debug.table-headers.definition-key'),
            $this->trans('commands.permission.debug.table-headers.definition-value')
        ];
        $tableRows = [];
        foreach ($definition as $key => $value) {
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = (string) $value;
            } elseif (is_array($value) || is_object($value)) {
                $value = Yaml::dump($value);
            } elseif (is_bool($value)) {
                $value = ($value) ? 'TRUE' : 'FALSE';
            }
            $tableRows[$key] = [$key, $value];
        }
        ksort($tableRows);
        $io->table($tableHeader, array_values($tableRows));
        return true;
    }
}
