<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRestResourceCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ServicesTrait;
use Drupal\Console\Command\ModuleTrait;
use Drupal\Console\Command\FormTrait;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Drupal\Console\Generator\PluginRestResourceGenerator;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\GeneratorCommand;
use Drupal\Console\Style\DrupalStyle;

class PluginRestResourceCommand extends GeneratorCommand
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    protected function configure()
    {
        $this
            ->setName('generate:plugin:rest:resource')
            ->setDescription($this->trans('commands.generate.plugin.rest.resource.description'))
            ->setHelp($this->trans('commands.generate.plugin.rest.resource.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rest.resource.options.class')
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.service.options.name')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-id')
            )
            ->addOption(
                'plugin-label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-label')
            )
            ->addOption(
                'plugin-url',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-url')
            )
            ->addOption(
                'plugin-states',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rest.resource.options.plugin-states')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($output)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $plugin_id = $input->getOption('plugin-id');
        $plugin_label = $input->getOption('plugin-label');
        $plugin_url = $input->getOption('plugin-url');
        $plugin_states = $input->getOption('plugin-states');

        $this->getGenerator()
            ->generate($module, $class_name, $plugin_label, $plugin_id, $plugin_url, $plugin_states);

        $this->getChain()->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $stringUtils = $this->getStringHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output);
            $input->setOption('module', $module);
        }

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $output->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.class'),
                'DefaultRestResource',
                function ($class_name) use ($stringUtils) {
                    if (!strlen(trim($class_name))) {
                        throw new \Exception('The Class name can not be empty');
                    }

                    return $stringUtils->humanToCamelCase($class_name);
                }
            );
            $input->setOption('class', $class_name);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $output->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-id'),
                $stringUtils->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --plugin-label option
        $plugin_label = $input->getOption('plugin-label');
        if (!$plugin_label) {
            $plugin_label = $output->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-label'),
                $this->getStringHelper()->camelCaseToHuman($class_name)
            );
            $input->setOption('plugin-label', $plugin_label);
        }

        // --plugin-url option
        $plugin_url = $input->getOption('plugin-url');
        if (!$plugin_url) {
            $plugin_url = $output->ask(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-url')
            );
            $input->setOption('plugin-url', $plugin_url);
        }

        // --plugin-states option
        $plugin_states = $input->getOption('plugin-states');
        if (!$plugin_states) {
            $questionHelper = $this->getQuestionHelper();
            $states = array_combine(array('GET', 'PUT', 'POST', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'), array('GET', 'PUT', 'POST', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'));
            $question = new ChoiceQuestion(
                $this->trans('commands.generate.plugin.rest.resource.questions.plugin-states'),
                $states,
                '0'
            );

            $question->setMultiselect(true);
            $plugin_states = $questionHelper->ask($input, $output, $question);
            $output->writeln(
                $this->trans('commands.generate.plugin.rest.resource.messages.selected-states').' '.implode(
                    ', ',
                    $plugin_states
                )
            );

            $input->setOption('plugin-states', $plugin_states);
        }
    }

    protected function createGenerator()
    {
        return new PluginRestResourceGenerator();
    }
}
