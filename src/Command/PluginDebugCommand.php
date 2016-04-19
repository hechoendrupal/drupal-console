<?php

/**
 * @file
 * Contains \Drupal\Console\Command\PluginDebugCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class PluginDebugCommand
 * @package Drupal\Console\Command
 */
class PluginDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('plugin:debug')
          ->setDescription($this->trans('commands.plugin.debug.description'))
          ->setHelp($this->trans('commands.plugin.debug.help'))
          ->addArgument('type', InputArgument::OPTIONAL, $this->trans('commands.plugin.debug.arguments.type'))
          ->addArgument('id', InputArgument::OPTIONAL, $this->trans('commands.plugin.debug.arguments.id'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $pluginType = $input->getArgument('type');
        $pluginId = $input->getArgument('id');

        // No plugin type specified, show a list of plugin types.
        if (!$pluginType) {
            $tableHeader = [
              $this->trans('commands.plugin.debug.table-headers.plugin-type-name'),
              $this->trans('commands.plugin.debug.table-headers.plugin-type-class')
            ];
            $tableRows = [];
            foreach ($this->getServices() as $serviceId) {
                if (strpos($serviceId, 'plugin.manager.') === 0) {
                    $service = $this->getContainer()->get($serviceId);
                    $typeName = substr($serviceId, 15);
                    $class = get_class($service);
                    $tableRows[$typeName] = [$typeName, $class];
                }
            }
            ksort($tableRows);
            $io->table($tableHeader, array_values($tableRows));
            return TRUE;
        }

        $service = $this->getService('plugin.manager.' . $pluginType);
        if (!$service) {
            $io->error(
              sprintf(
                $this->trans('commands.plugin.debug.errors.plugin-type-not-found'),
                $pluginType)
            );
            return FALSE;
        }

        // Valid plugin type specified, no ID specified, show list of instances.
        if (!$pluginId) {
            $tableHeader = [
              $this->trans('commands.plugin.debug.table-headers.plugin-id'),
              $this->trans('commands.plugin.debug.table-headers.plugin-class')
            ];
            $tableRows = [];
            foreach ($service->getDefinitions() as $definition) {
                $pluginId = $definition['id'];
                $className = $definition['class'];
                $tableRows[$pluginId] = [$pluginId, $className];
            }
            ksort($tableRows);
            $io->table($tableHeader, array_values($tableRows));
            return TRUE;
        }

        // Valid plugin type specified, ID specified, show the definition.
        $definition = $service->getDefinition($pluginId);
        $tableHeader = [
          $this->trans('commands.plugin.debug.table-headers.definition-key'),
          $this->trans('commands.plugin.debug.table-headers.definition-value')
        ];
        $tableRows = [];
        foreach ($definition as $key => $value) {
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = (string) $value;
            }
            elseif (is_array($value) || is_object($value)) {
                $value = var_export($value, TRUE);
            }
            elseif (is_bool($value)) {
                $value = ($value) ? 'TRUE' : 'FALSE';
            }
            $tableRows[$key] = [$key, $value];
        }
        ksort($tableRows);
        $io->table($tableHeader, array_values($tableRows));
        return TRUE;
    }
}