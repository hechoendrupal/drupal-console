<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;

class RestDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rest:debug')
            ->setDescription($this->trans('commands.rest.debug.description'))
            ->addArgument(
                'resource-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.rest.debug.arguments.resource-id')
            )
            ->addOption(
                'authorization',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.rest.debug.options.status')
            );

        $this->addDependency('rest');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resource_id = $input->getArgument('resource-id');
        $status = $input->getOption('authorization');

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);

        if ($resource_id) {
            $this->getRestByID($output, $table, $resource_id);
        } else {
            $this->getAllRestResources($status, $output, $table);
        }
    }

    /**
     *
     * @param $output         OutputInterface
     * @param $table          TableHelper
     * @param $resource_id    String
     */
    private function getRestByID($output, $table, $resource_id)
    {
        // Get the list of enabled and disabled resources.
        $config = $this->getRestDrupalConfig();

        $resourcePluginManager = $this->getPluginManagerRest();
        $plugin = $resourcePluginManager->getInstance(array('id' => $resource_id));

        if (empty($plugin)) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.rest.debug.messages.not-found'),
                    $resource_id
                ).'</error>'
            );

            return false;
        }

        $resource = $plugin->getPluginDefinition();

        $configuration = array();
        $configuration[$this->trans('commands.rest.debug.messages.id')] = $resource['id'];
        $configuration[$this->trans('commands.rest.debug.messages.label')] = (string) $resource['label'];
        $configuration[$this->trans('commands.rest.debug.messages.canonical_url')] = $resource['uri_paths']['canonical'];
        $configuration[$this->trans('commands.rest.debug.messages.status')] = (isset($config[$resource['id']])) ? $this->trans('commands.rest.debug.messages.enabled') : $this->trans('commands.rest.debug.messages.disabled');
        $configuration[$this->trans('commands.rest.debug.messages.provider')] = $resource['provider'];

        $configurationEncoded = Yaml::encode($configuration);

        $output->writeln($configurationEncoded);

        $table->render($output);

        $table->setHeaders(
            [
            $this->trans('commands.rest.debug.messages.rest-state'),
            $this->trans('commands.rest.debug.messages.supported-formats'),
            $this->trans('commands.rest.debug.messages.supported_auth'),
            ]
        );

        foreach ($config[$resource['id']] as $method => $settings) {
            $table->addRow(
                [
                $method,
                implode(', ', $settings['supported_formats']),
                implode(', ', $settings['supported_auth']),
                ]
            );
        }

        $table->render($output);
    }

    protected function getAllRestResources($status, $output, $table)
    {
        $rest_resources = $this->getRestResources($status);

        $table->setHeaders(
            [
            $this->trans('commands.rest.debug.messages.id'),
            $this->trans('commands.rest.debug.messages.label'),
            $this->trans('commands.rest.debug.messages.canonical_url'),
            $this->trans('commands.rest.debug.messages.status'),
            $this->trans('commands.rest.debug.messages.provider'),
            ]
        );

        $table->setlayout($table::LAYOUT_COMPACT);

        foreach ($rest_resources as $status => $resources) {
            foreach ($resources as $id => $resource) {
                $table->addRow(
                    [
                    $id,
                    $resource['label'],
                    $resource['uri_paths']['canonical'],
                    $status,
                    $resource['provider'],
                    ]
                );
            }
        }
        $table->render($output);
    }
}
