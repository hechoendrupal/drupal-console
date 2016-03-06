<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Rest\DebugCommand.
 */

namespace Drupal\Console\Command\Rest;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Component\Serialization\Yaml;
use Drupal\Console\Style\DrupalStyle;

class DebugCommand extends ContainerAwareCommand
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
        $io = new DrupalStyle($input, $output);

        $resource_id = $input->getArgument('resource-id');
        $status = $input->getOption('authorization');

        if ($resource_id) {
            $this->restDetail($io, $resource_id);
        } else {
            $this->restList($io, $status);
        }
    }

    private function restDetail(DrupalStyle $io, $resource_id)
    {
        $config = $this->getRestDrupalConfig();

        $resourcePluginManager = $this->getPluginManagerRest();
        $plugin = $resourcePluginManager->getInstance(array('id' => $resource_id));

        if (empty($plugin)) {
            $io->error(
                sprintf(
                    $this->trans('commands.rest.debug.messages.not-found'),
                    $resource_id
                )
            );

            return false;
        }

        $resource = $plugin->getPluginDefinition();

        $configuration = [];
        $configuration[] = [$this->trans('commands.rest.debug.messages.id'), $resource['id']];
        $configuration[] = [$this->trans('commands.rest.debug.messages.label'), (string) $resource['label']];
        $configuration[] = [$this->trans('commands.rest.debug.messages.canonical_url'), $resource['uri_paths']['canonical']];
        $configuration[] = [$this->trans('commands.rest.debug.messages.status'), (isset($config[$resource['id']])) ? $this->trans('commands.rest.debug.messages.enabled') : $this->trans('commands.rest.debug.messages.disabled')];
        $configuration[] = [$this->trans('commands.rest.debug.messages.provider', $resource['provider'])];

        $io->comment($resource_id);
        $io->newLine();

        $io->table([], $configuration, 'compact');

        $tableHeader = [
          $this->trans('commands.rest.debug.messages.rest-state'),
          $this->trans('commands.rest.debug.messages.supported-formats'),
          $this->trans('commands.rest.debug.messages.supported_auth'),
        ];

        $tableRows = [];
        foreach ($config[$resource['id']] as $method => $settings) {
            $tableRows[] = [
                $method,
                implode(', ', $settings['supported_formats']),
                implode(', ', $settings['supported_auth']),
              ];
        }

        $io->table($tableHeader, $tableRows);
    }

    protected function restList(DrupalStyle $io, $status)
    {
        $rest_resources = $this->getRestResources($status);

        $tableHeader = [
          $this->trans('commands.rest.debug.messages.id'),
          $this->trans('commands.rest.debug.messages.label'),
          $this->trans('commands.rest.debug.messages.canonical_url'),
          $this->trans('commands.rest.debug.messages.status'),
          $this->trans('commands.rest.debug.messages.provider'),
        ];

        $tableRows = [];
        foreach ($rest_resources as $status => $resources) {
            foreach ($resources as $id => $resource) {
                $tableRows[] =[
                  $id,
                  $resource['label'],
                  $resource['uri_paths']['canonical'],
                  $status,
                  $resource['provider'],
                ];
            }
        }
        $io->table($tableHeader, $tableRows, 'compact');
    }
}
