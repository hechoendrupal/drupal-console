<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\RestEnableCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\OutputInterface;

class RestEnableCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('rest:enable')
          ->setDescription($this->trans('commands.rest.enable.description'))
          ->addArgument('resource-id', InputArgument::OPTIONAL,
            $this->trans('commands.rest.debug.arguments.resource-id'));

        $this->addDependency('rest');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $questionHelper = $this->getQuestionHelper();
        $resource_id = $input->getArgument('resource-id');
        $rest_resources = $this->getRestResources();
        $rest_resources_ids = array_merge(array_keys($rest_resources['enabled']),
          array_keys($rest_resources['disabled']));

        if (!$resource_id) {
            $resource_id = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.rest.enable.arguments.resource-id'), ''),
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

        // Calculate states available by resource and generate the question
        $resourcePluginManager = $this->getPluginManagerRest();
        $plugin = $resourcePluginManager->getInstance(array('id' => $resource_id));

        $states = $plugin->availableMethods();
        $question = new ChoiceQuestion(
          $this->trans('commands.rest.enable.arguments.states'),
          $states,
          '0'
        );

        $state = $questionHelper->ask($input, $output, $question);
        $output->writeln($this->trans('commands.rest.enable.messages.selected-state').' '.$state);

        // Get serializer formats available and generate the question.

        $formats = $this->getSerializerFormats();
        $question = new ChoiceQuestion(
          $this->trans('commands.rest.enable.messages.formats'),
          $formats,
          '0'
        );

        $question->setMultiselect(true);
        $formats = $questionHelper->ask($input, $output, $question);
        $output->writeln($this->trans('commands.rest.enable.messages.selected-formats').' '.implode(', ',
            $formats));

        // Get Authentication Provider and generate the question
        $authentication_providers = $this->getAuthenticationProviders();

        $question = new ChoiceQuestion(
          $this->trans('commands.rest.enable.messages.authentication-providers'),
          array_keys($authentication_providers),
          '0'
        );

        $question->setMultiselect(true);
        $authentication_providers = $questionHelper->ask($input, $output, $question);
        $output->writeln($this->trans('commands.rest.enable.messages.selected-authentication-providers').' '.implode(', ',
            $authentication_providers));

        $rest_settings = $this->getRestDrupalConfig();

        $rest_settings[$resource_id][$state]['supported_formats'] = $formats;
        $rest_settings[$resource_id][$state]['supported_auth'] = $authentication_providers;

        $config = $this->getConfigFactory()
          ->getEditable('rest.settings');
        $config->set('resources', $rest_settings);
        $config->save();
    }
}
