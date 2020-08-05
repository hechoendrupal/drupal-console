<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRulesActionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\PluginRulesDataprocessorGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PluginRulesDataprocessorCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginRulesDataprocessorCommand extends Command
{

    use ConfirmationTrait;
    use ModuleTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var PluginRulesDataprocessorGenerator
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
     * PluginRulesDataprocessorCommand constructor.
     *
     * @param Manager $extensionManager
     * @param PluginRulesDataprocessorGenerator $generator
     * @param StringConverter $stringConverter
     * @param Validator $validator
     * @param ChainQueue $chainQueue
     */
    public function __construct(
      Manager $extensionManager,
      PluginRulesDataprocessorGenerator $generator,
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
          ->setName('generate:plugin:rules:dataprocessor')
          ->setDescription($this->trans('commands.generate.plugin.rules.dataprocessor.description'))
          ->setHelp($this->trans('commands.generate.plugin.rules.dataprocessor.help'))
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
            $this->trans('commands.generate.plugin.rules.dataprocessor.options.class')
          )
          ->addOption(
            'label',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rules.dataprocessor.options.label')
          )
          ->addOption(
            'plugin-id',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.plugin.rules.dataprocessor.options.plugin-id')
          )
          ->setAliases(['gprd']);
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

        $module = $input->getOption('module');
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
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
              $this->trans('commands.generate.plugin.rules.dataprocessor.options.class'),
              'DefaultDataprocessor',
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
              $this->trans('commands.generate.plugin.rules.dataprocessor.options.label'),
              $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
              $this->trans('commands.generate.plugin.rules.dataprocessor.options.plugin-id'),
              $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }
    }
}