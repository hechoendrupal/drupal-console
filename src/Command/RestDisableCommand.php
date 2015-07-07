<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestDisableCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestDisableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('rest:disable')
          ->setDescription($this->trans('commands.rest.disable.description'))
          ->addArgument(
              'resource-id',
              InputArgument::OPTIONAL,
              $this->trans('commands.rest.debug.arguments.resource-id')
          );

        $this->addDependency('rest');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $resource_id = $input->getArgument('resource-id');
        $rest_resources = $this->getRestResources();
        $rest_resources_ids = array_merge(
            array_keys($rest_resources['enabled']),
            array_keys($rest_resources['disabled'])
        );

        if (!$resource_id) {
            $resource_id = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.rest.disable.arguments.resource-id'), ''),
                function ($resource_id) use ($rest_resources_ids) {
                    return $this->validateRestResource($resource_id, $rest_resources_ids, $this->getTranslator());
                },
                false,
                '',
                $rest_resources_ids
            );
        }

        $this->validateRestResource($resource_id, $rest_resources_ids, $this->getTranslator());
        $input->setArgument('resource-id', $resource_id);
        $rest_settings = $this->getRestDrupalConfig();

        unset($rest_settings[$resource_id]);

        $config = $this->getConfigFactory()
          ->getEditable('rest.settings');

        $config->set('resources', $rest_settings);
        $config->save();
    }
}
