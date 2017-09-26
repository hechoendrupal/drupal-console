<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

class PluginFieldCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginFieldCommand constructor.
     *
     * @param Manager         $extensionManager
     * @param StringConverter $stringConverter
     * @param Validator       $validator
     * @param ChainQueue      $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        StringConverter $stringConverter,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:field')
            ->setDescription($this->trans('commands.generate.plugin.field.description'))
            ->setHelp($this->trans('commands.generate.plugin.field.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'type-class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.type-class')
            )
            ->addOption(
                'type-label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-label')
            )
            ->addOption(
                'type-plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-plugin-id')
            )
            ->addOption(
                'type-description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.type-description')
            )
            ->addOption(
                'formatter-class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.formatter-class')
            )
            ->addOption(
                'formatter-label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.formatter-label')
            )
            ->addOption(
                'formatter-plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.formatter-plugin-id')
            )
            ->addOption(
                'widget-class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.field.options.formatter-class')
            )
            ->addOption(
                'widget-label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.widget-label')
            )
            ->addOption(
                'widget-plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.widget-plugin-id')
            )
            ->addOption(
                'field-type',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.field-type')
            )
            ->addOption(
                'default-widget',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.default-widget')
            )
            ->addOption(
                'default-formatter',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.field.options.default-formatter')
            )
            ->setAliases(['gpf']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return 1;
        }

        $this->chainQueue
            ->addCommand(
                'generate:plugin:fieldtype', [
                '--module' => $input->getOption('module'),
                '--class' => $this->validator->validateClassName($input->getOption('type-class')),
                '--label' => $input->getOption('type-label'),
                '--plugin-id' => $input->getOption('type-plugin-id'),
                '--description' => $input->getOption('type-description'),
                '--default-widget' => $input->getOption('default-widget'),
                '--default-formatter' => $input->getOption('default-formatter'),
                ],
                false
            );

        $this->chainQueue
            ->addCommand(
                'generate:plugin:fieldwidget', [
                '--module' => $input->getOption('module'),
                '--class' => $this->validator->validateClassName($input->getOption('widget-class')),
                '--label' => $input->getOption('widget-label'),
                '--plugin-id' => $input->getOption('widget-plugin-id'),
                '--field-type' => $input->getOption('field-type'),
                ],
                false
            );
        $this->chainQueue
            ->addCommand(
                'generate:plugin:fieldformatter', [
                '--module' => $input->getOption('module'),
                '--class' => $this->validator->validateClassName($input->getOption('formatter-class')),
                '--label' => $input->getOption('formatter-label'),
                '--plugin-id' => $input->getOption('formatter-plugin-id'),
                '--field-type' => $input->getOption('field-type'),
                ],
                false
            );

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery'], false);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --module option
        $module = $input->getOption('module');
        if (!$module) {
            // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
            $module = $this->moduleQuestion($io);
            $input->setOption('module', $module);
        }

        // --type-class option
        $typeClass = $input->getOption('type-class');
        if (!$typeClass) {
            $typeClass = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-class'),
                'ExampleFieldType',
                function ($typeClass) {
                    return $this->validator->validateClassName($typeClass);
                }
            );
            $input->setOption('type-class', $typeClass);
        }

        // --type-label option
        $label = $input->getOption('type-label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-label'),
                $this->stringConverter->camelCaseToHuman($typeClass)
            );
            $input->setOption('type-label', $label);
        }

        // --type-plugin-id option
        $plugin_id = $input->getOption('type-plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($typeClass)
            );
            $input->setOption('type-plugin-id', $plugin_id);
        }

        // --type-description option
        $description = $input->getOption('type-description');
        if (!$description) {
            $description = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.type-description'),
                $this->trans('commands.generate.plugin.field.suggestions.my-field-type')
            );
            $input->setOption('type-description', $description);
        }

        // --widget-class option
        $widgetClass = $input->getOption('widget-class');
        if (!$widgetClass) {
            $widgetClass = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.widget-class'),
                'ExampleWidgetType',
                function ($widgetClass) {
                    return $this->validator->validateClassName($widgetClass);
                }
            );
            $input->setOption('widget-class', $widgetClass);
        }

        // --widget-label option
        $widgetLabel = $input->getOption('widget-label');
        if (!$widgetLabel) {
            $widgetLabel = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.widget-label'),
                $this->stringConverter->camelCaseToHuman($widgetClass)
            );
            $input->setOption('widget-label', $widgetLabel);
        }

        // --widget-plugin-id option
        $widget_plugin_id = $input->getOption('widget-plugin-id');
        if (!$widget_plugin_id) {
            $widget_plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.widget-plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($widgetClass)
            );
            $input->setOption('widget-plugin-id', $widget_plugin_id);
        }

        // --formatter-class option
        $formatterClass = $input->getOption('formatter-class');
        if (!$formatterClass) {
            $formatterClass = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.formatter-class'),
                'ExampleFormatterType',
                function ($formatterClass) {
                    return $this->validator->validateClassName($formatterClass);
                }
            );
            $input->setOption('formatter-class', $formatterClass);
        }

        // --formatter-label option
        $formatterLabel = $input->getOption('formatter-label');
        if (!$formatterLabel) {
            $formatterLabel = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.formatter-label'),
                $this->stringConverter->camelCaseToHuman($formatterClass)
            );
            $input->setOption('formatter-label', $formatterLabel);
        }

        // --formatter-plugin-id option
        $formatter_plugin_id = $input->getOption('formatter-plugin-id');
        if (!$formatter_plugin_id) {
            $formatter_plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.formatter-plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($formatterClass)
            );
            $input->setOption('formatter-plugin-id', $formatter_plugin_id);
        }

        // --field-type option
        $field_type = $input->getOption('field-type');
        if (!$field_type) {
            $field_type = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.field-type'),
                $plugin_id
            );
            $input->setOption('field-type', $field_type);
        }

        // --default-widget option
        $default_widget = $input->getOption('default-widget');
        if (!$default_widget) {
            $default_widget = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.default-widget'),
                $widget_plugin_id
            );
            $input->setOption('default-widget', $default_widget);
        }

        // --default-formatter option
        $default_formatter = $input->getOption('default-formatter');
        if (!$default_formatter) {
            $default_formatter = $io->ask(
                $this->trans('commands.generate.plugin.field.questions.default-formatter'),
                $formatter_plugin_id
            );
            $input->setOption('default-formatter', $default_formatter);
        }
    }
}
