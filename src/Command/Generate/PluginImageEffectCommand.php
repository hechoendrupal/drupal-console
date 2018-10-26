<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginImageEffectCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginImageEffectGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class PluginImageEffectCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginImageEffectCommand extends Command
{
    use ModuleTrait;
    use ConfirmationTrait;

    /**
 * @var Manager
*/
    protected $extensionManager;

    /**
 * @var PluginImageEffectGenerator
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
     * PluginImageEffectCommand constructor.
     *
     * @param Manager                    $extensionManager
     * @param PluginImageEffectGenerator $generator
     * @param StringConverter            $stringConverter
     * @param Validator                  $validator
     * @param ChainQueue                 $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        PluginImageEffectGenerator $generator,
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
            ->setName('generate:plugin:imageeffect')
            ->setDescription($this->trans('commands.generate.plugin.imageeffect.description'))
            ->setHelp($this->trans('commands.generate.plugin.imageeffect.help'))
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
                $this->trans('commands.generate.plugin.imageeffect.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.imageeffect.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.imageeffect.options.plugin-id')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.imageeffect.options.description')
            )
            ->setAliases(['gpie']);
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
        $description = $input->getOption('description');

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'description' => $description,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.imageeffect.questions.class'),
                'DefaultImageEffect',
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
                $this->trans('commands.generate.plugin.imageeffect.questions.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.imageeffect.questions.plugin-id'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        // --description option
        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.imageeffect.questions.description'),
                $this->trans('commands.generate.plugin.imageeffect.suggestions.my-image-effect')
            );
            $input->setOption('description', $description);
        }
    }
}
