<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Rest\EnableCommand.
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
use \Drupal\Console\Helper\HelperTrait;

class EnableCommand extends Command
{
    use ContainerAwareCommandTrait;
    use RestTrait;
    use HelperTrait;

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
            ->setName('rest:enable')
            ->setDescription($this->trans('commands.rest.enable.description'))
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
            $resource_id = $io->choiceNoList(
                $this->trans('commands.rest.enable.arguments.resource-id'),
                $rest_resources_ids
            );
        }

        $this->validateRestResource(
            $resource_id,
            $rest_resources_ids,
            $this->getTranslator()
        );
        $input->setArgument('resource-id', $resource_id);

        // Calculate states available by resource and generate the question
        $resourcePluginManager = $this->getDrupalService('plugin.manager.rest');
        $plugin = $resourcePluginManager->getInstance(['id' => $resource_id]);

        $states = $plugin->availableMethods();

        $state = $io->choice(
            $this->trans('commands.rest.enable.arguments.states'),
            $states
        );
        $io->writeln(
            $this->trans('commands.rest.enable.messages.selected-state').' '.$state
        );

        // Get Authentication Provider and generate the question
        $authenticationProviders = $this->getDrupalService('authentication_collector')
            ->getSortedProviders();

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

        $config = $this->getDrupalService('config.factory')
            ->getEditable('rest.settings');
        $config->set('resources', $rest_settings);
        $config->save();

        // Run cache rebuild to enable rest routing
        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'all']);
    }
}
