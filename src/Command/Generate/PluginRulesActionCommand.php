<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginRulesActionCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Generator\PluginRulesActionGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\StringConverter;
use Drupal\Console\Utils\ChainQueue;

/**
 * Class PluginRulesActionCommand
 * @package Drupal\Console\Command\Generate
 */
class PluginRulesActionCommand extends Command
{
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;
    use CommandTrait;

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
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * PluginRulesActionCommand constructor.
     * @param Manager                    $extensionManager
     * @param PluginRulesActionGenerator $generator
     * @param StringConverter            $stringConverter
     * @param ChainQueue                 $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginRulesActionGenerator $generator,
        StringConverter $stringConverter,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:rulesaction')
            ->setDescription($this->trans('commands.generate.plugin.rulesaction.description'))
            ->setHelp($this->trans('commands.generate.plugin.rulesaction.help'))
            ->addOption('module', '', InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addOption(
                'class',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.class')
            )
            ->addOption(
                'label',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.label')
            )
            ->addOption(
                'plugin-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id')
            )
            ->addOption('type', '', InputOption::VALUE_REQUIRED, $this->trans('commands.generate.plugin.rulesaction.options.type'))
            ->addOption(
                'category',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.rulesaction.options.category')
            )
            ->addOption(
                'context',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.rulesaction.options.context')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');
        $type = $input->getOption('type');
        $category = $input->getOption('category');
        $context = $input->getOption('context');

        $this->generator->generate($module, $class_name, $label, $plugin_id, $category, $context, $type);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
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

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.class'),
                'DefaultAction'
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --type option
        $type = $input->getOption('type');
        if (!$type) {
            $type = $io->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.type'),
                'user'
            );
            $input->setOption('type', $type);
        }

        // --category option
        $category = $input->getOption('category');
        if (!$category) {
            $category = $io->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.category'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('category', $category);
        }

        // --context option
        $context = $input->getOption('context');
        if (!$context) {
            $context = $io->ask(
                $this->trans('commands.generate.plugin.rulesaction.options.context'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('context', $context);
        }
    }
}
