<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRulesActionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginRulesActionGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class PluginRulesActionCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginRulesActionCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use ConfirmationTrait;

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
     * @param Manager                    $extensionManager
     * @param PluginRulesActionGenerator $generator
     * @param StringConverter            $stringConverter
     * @param Validator                  $validator
     * @param ChainQueue                 $chainQueue
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
            ->setName('generate:plugin:rulesaction')
            ->setDescription($this->trans('commands.generate.plugin.rulesaction.description'))
            ->setHelp($this->trans('commands.generate.plugin.rulesaction.help'))
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
                $this->trans('commands.generate.plugin.rulesaction.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id')
            )
            ->addOption('type', null, InputOption::VALUE_REQUIRED, $this->trans('commands.generate.plugin.rulesaction.options.type'))
            ->addOption(
                'category',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rulesaction.options.category')
            )
            ->addOption(
                'context',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.context')
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

        $module = $input->getOption('module');
        $class_name = $this->validator->validateClassName($input->getOption('class'));
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $type = $input->getOption('type');
        $category = $input->getOption('category');
        $context = $input->getOption('context');

        $this->generator->generate([
            'module' => $module,
            'class_name' => $class_name,
            'label' => $label,
            'plugin_id' => $plugin_id,
            'category' => $category,
            'context' => $context,
            'type' => $type,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

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
                $this->trans('commands.generate.plugin.rulesaction.options.class'),
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
                $this->trans('commands.generate.plugin.rulesaction.options.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --type option
        $type = $input->getOption('type');
        if (!$type) {
            $type = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.type'),
                'user'
            );
            $input->setOption('type', $type);
        }

        // --category option
        $category = $input->getOption('category');
        if (!$category) {
            $category = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.category'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('category', $category);
        }

        // --context option
        $context = $input->getOption('context');
        if (!$context) {
            $context = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.context'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('context', $context);
        }
    }
}
