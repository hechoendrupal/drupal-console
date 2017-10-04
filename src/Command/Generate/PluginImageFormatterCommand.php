<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginFieldFormatterCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginImageFormatterGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Core\Utils\ChainQueue;

class PluginImageFormatterCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginImageFormatterGenerator
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
     * PluginImageFormatterCommand constructor.
     *
     * @param Manager                       $extensionManager
     * @param PluginImageFormatterGenerator $generator
     * @param StringConverter               $stringConverter
     * @param Validator                     $validator
     * @param ChainQueue                    $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginImageFormatterGenerator $generator,
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
            ->setName('generate:plugin:imageformatter')
            ->setDescription($this->trans('commands.generate.plugin.imageformatter.description'))
            ->setHelp($this->trans('commands.generate.plugin.imageformatter.help'))
            ->addOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.imageformatter.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.imageformatter.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.imageformatter.options.plugin-id')
            )
            ->setAliases(['gpif']);
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

        $module = $input->getOption('module');
        $class_name = $input->getOption('class');
        $label = $input->getOption('label');
        $plugin_id = $input->getOption('plugin-id');

        $this->generator->generate($module, $class_name, $label, $plugin_id);
        
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
        }
        $input->setOption('module', $module);

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.plugin.imageformatter.questions.class'),
                'ExampleImageFormatter'
            );
            $input->setOption('class', $class_name);
        }

        // --label option
        $label = $input->getOption('label');
        if (!$label) {
            $label = $io->ask(
                $this->trans('commands.generate.plugin.imageformatter.questions.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $io->ask(
                $this->trans('commands.generate.plugin.imageformatter.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }
    }
}
