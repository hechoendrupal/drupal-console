<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Rest\EnableCommand.
 */

namespace Drupal\Console\Command\Rest;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

class EnableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rest:enable')
            ->setDescription($this->trans('commands.rest.enable.description'))
            ->addArgument(
                'resource-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.rest.debug.arguments.resource-id')
            );

        $this->addDependency('rest');
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
            $resource_id = $io->choiceNoList(
                $this->trans('commands.rest.enable.arguments.resource-id'),
                $rest_resources_ids
            );
        }

        $this->validateRestResource($resource_id, $rest_resources_ids, $this->getTranslator());
        $input->setArgument('resource-id', $resource_id);

        // Calculate states available by resource and generate the question
        $resourcePluginManager = $this->getPluginManagerRest();
        $plugin = $resourcePluginManager->getInstance(array('id' => $resource_id));

        $states = $plugin->availableMethods();

        $state = $io->choice(
            $this->trans('commands.rest.enable.arguments.states'),
            $states
        );
        $io->writeln($this->trans('commands.rest.enable.messages.selected-state').' '.$state);

        // Get serializer formats available and generate the question.
        $serializedFormats = $this->getSerializerFormats();
        $formats = $io->choice(
            $this->trans('commands.rest.enable.messages.formats'),
            $serializedFormats,
            0,
            true
        );

        $io->writeln(
            $this->trans('commands.rest.enable.messages.selected-formats').' '.implode(
                ', ',
                $formats
            )
        );

        // Get Authentication Provider and generate the question
        $authenticationProviders = $this->getAuthenticationProviders();

        $authenticationProvidersSelected = $io->choice(
            $this->trans('commands.rest.enable.messages.authentication-providers'),
            array_keys($authenticationProviders),
            0,
            true
        );

        $io->writeln(
            $this->trans('commands.rest.enable.messages.selected-authentication-providers').' '.implode(
                ', ',
                $authenticationProvidersSelected
            )
        );

        $rest_settings = $this->getRestDrupalConfig();

        $rest_settings[$resource_id][$state]['supported_formats'] = $formats;
        $rest_settings[$resource_id][$state]['supported_auth'] = $authenticationProvidersSelected;

        $config = $this->getConfigFactory()
            ->getEditable('rest.settings');
        $config->set('resources', $rest_settings);
        $config->save();

        // Run cache rebuild to enable rest routing
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
