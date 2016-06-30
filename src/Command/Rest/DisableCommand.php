<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Rest\DisableCommand.
 */

namespace Drupal\Console\Command\Rest;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Annotation\DrupalCommand;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\RestTrait;

class DisableCommand extends Command
{
    use ContainerAwareCommandTrait;
    use RestTrait;

    /**
     * @DrupalCommand(
     *     dependencies = {
     *         “rest"
     *     }
     * )
     */
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $resource_id = $input->getArgument('resource-id');
        $rest_resources = $this->getRestResources();
        $rest_resources_ids = array_merge(
            array_keys($rest_resources['enabled']),
            array_keys($rest_resources['disabled'])
        );

        if (!$resource_id) {
            $resource_id = $io->choice(
                $this->trans('commands.rest.disable.arguments.resource-id'),
                $rest_resources_ids
            );
        }

        $this->validateRestResource(
          $resource_id,
          $rest_resources_ids,
          $this->getTranslator()
        );
        $input->setArgument('resource-id', $resource_id);
        $rest_settings = $this->getRestDrupalConfig();

        unset($rest_settings[$resource_id]);

        $config = $this->getDrupalService('config.factory')
            ->getEditable('rest.settings');

        $config->set('resources', $rest_settings);
        $config->save();
    }
}
