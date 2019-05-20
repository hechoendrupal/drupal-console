<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRulesActionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\PluginRulesActionGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PluginRulesActionCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginRulesActionCommand extends Command
{

    use ArrayInputTrait;
    use ConfirmationTrait;
    use ModuleTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var PluginRulesActionGenerator
     */
    protected $generator;

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
     * PluginRulesActionCommand constructor.
     *
     * @param Manager $extensionManager
     * @param PluginRulesActionGenerator $generator
     * @param StringConverter $stringConverter
     * @param Validator $validator
     * @param ChainQueue $chainQueue
     */
    public function __construct(
      Manager $extensionManager,
      PluginRulesActionGenerator $generator,
      StringConverter $stringConverter,
      Validator $validator,
      ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
          ->setName('generate:plugin:rules:action')
          ->setDescription($this->trans('commands.generate.plugin.rules.action.description'))
          ->setHelp($this->trans('commands.generate.plugin.rules.action.help'))
          ->addOption(
            'module',
            null,
            InputOption::VALUE_REQUIRED,
            $this->trans('commands.common.options.module')
          )
          ->addOption(
            'class',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rules.action.options.class')
          )
          ->addOption(
            'label',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rules.action.options.label')
          )
          ->addOption(
            'plugin-id',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rules.action.options.plugin-id')
          )
          ->addOption(
            'category',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rules.action.options.category')
          )
          ->addOption(
            'context',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            $this->trans('commands.generate.plugin.rules.action.options.context')
          )
          ->setAliases(['gpra']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $module = $this->validateModule($input->getOption('module'));
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $category = $input->getOption('category');
        $context = $input->getOption('context');
        $noInteraction = $input->getOption('no-interaction');

        // Parse nested data.
        if ($noInteraction) {
            $context = $this->explodeInlineArray($context);
        }

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'category' => $category,
          'context' => $context,
        ]);

        $this->chainQueue->addCommand('cache:rebuild',
          ['cache' => 'discovery']);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.rules.action.options.class'),
              'DefaultAction',
              function ($class_name) {
                  return $this->validator->validateClassName($class_name);
              }
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.rules.action.options.label'),
              $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.rules.action.options.plugin-id'),
              $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --category option
        $category = $input->getOption('category');
        if (!$category) {
            $category = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.rules.action.options.category'),
              $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('category', $category);
        }

        // --context option
        $context = $input->getOption('context');
        if (empty($context)) {

            $context = [];
            if ($this->getIo()->confirm(
              $this->trans('commands.generate.plugin.rules.action.questions.context'),
              true
            )) {
                while (true) {
                    $this->getIo()->newLine();

                    $input_name = $this->getIo()->ask(
                      $this->trans('commands.generate.plugin.rules.action.questions.context-name')
                    );

                    $input_type = $this->getIo()->ask(
                      $this->trans('commands.generate.plugin.rules.action.questions.context-type')
                    );

                    $input_label = $this->getIo()->ask(
                      $this->trans('commands.generate.plugin.rules.action.questions.context-label')
                    );

                    $input_description = $this->getIo()->ask(
                      $this->trans('commands.generate.plugin.rules.action.questions.context-description')
                    );

                    array_push(
                      $context,
                      [
                        'name' => $input_name,
                        'type' => $input_type,
                        'label' => $input_label,
                        'description' => $input_description,
                      ]
                    );

                    $this->getIo()->newLine();
                    if (!$this->getIo()->confirm(
                      $this->trans('commands.generate.plugin.rules.action.questions.another-context'),
                      true
                    )) {
                        break;
                    }
                }
            }
        } else {
            $context = $this->explodeInlineArray($context);
        }

        $input->setOption('context', $context);
    }
}
