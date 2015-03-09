<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\GeneratorCommandCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ModuleTrait;
use Drupal\AppConsole\Command\Helper\AnnotationTrait;
use Drupal\AppConsole\Generator\PluginManagerGenerator;

class GeneratorPluginManagerCommand extends GeneratorCommand
{
    use ModuleTrait;
    use AnnotationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate a new plugin type')
            ->setHelp('The <info>generate:plugin:manager</info> command helps you generate a new plugin type.')
            ->setName('generate:plugin:manager')

        ->addOption('module',
            $shorcut = null,
            $mode = InputOption::VALUE_REQUIRED,
            $description = 'The name of module.',
            $default = null
        )
        ->addOption('plugin-manager',
            $shorcut = null,
            $mode = InputOption::VALUE_REQUIRED,
            $description = 'Plugin manager name.',
            $default = null
        )

        ->addOption('annotation',
            $shorcut = null,
            $mode = InputOption::VALUE_REQUIRED,
            $description = 'Name to annotation.',
            $default = null
        )

        ->addOption('annotation-property',
            $shorcut = null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            $description = 'Properties to annotation.',
            $default = null
        )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $plugin_manager = $this->validateClassName($input->getOption('plugin-manager'));
        $annotation = $this->validateClassName($input->getOption('annotation'));

        // @see \Drupal\AppConsole\Command\Helper\AnnotationTrait
        $annotation_property = $this->buildPropertiesAnnotation(
            $input->getOption('annotation-property')
        );

        /** @var \Drupal\AppConsole\Generator\PluginManagerGenerator $generator */
        $generator = $this->getGenerator();
        $generator->generate($module, $plugin_manager, $annotation, $annotation_property);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($output, $dialog);
        }
        $input->setOption('module', $module);

        $plugin_manager = $input->getOption('plugin-manager');
        if(!$plugin_manager) {
            $plugin_manager = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.plugin_manager.name'),
                    'DefaultPluginManager'
                ),
                function ($plugin_manager) {
                    return $this->validateClassName($plugin_manager);
                },
                $attemps = false,
                $default = 'DefaultPluginManager',
                $autocomplete = null
            );
        }
        $input->setOption('plugin-manager', $plugin_manager);

        // --annotation option
        $annotation = $input->getOption('annotation');
        if (!$annotation) {
            // @see \Drupal\AppConsole\Command\Helper\AnnotationTrait
            $annotation = $this->annotationQuestion($output, $dialog);
        }
        $input->setOption('annotation', $annotation);

        // --annotation-property option
        $annotation_property = $input->getOption('annotation-property');
        if (!$annotation_property) {
            // @see \Drupal\AppConsole\Command\Helper\AnnotationTrait
            $annotation_property = $this->annotationPropertyQuestion($output, $dialog);
        }
        $input->setOption('annotation-property', $annotation_property);

    }

    /**
     * @return PluginManagerGenerator
     */
    protected function createGenerator()
    {
        return new PluginManagerGenerator();
    }

}
