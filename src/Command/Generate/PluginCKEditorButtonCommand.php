<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginCKEditorButtonCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Generator\PluginCKEditorButtonGenerator;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginCKEditorButtonCommand extends Command
{
    use ArrayInputTrait;
    use ConfirmationTrait;
    use ModuleTrait;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;


    /**
     * @var PluginCKEditorButtonGenerator
     */
    protected $generator;

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
     * PluginCKEditorButtonCommand constructor.
     *
     * @param ChainQueue                    $chainQueue
     * @param PluginCKEditorButtonGenerator $generator
     * @param Manager                       $extensionManager
     * @param StringConverter               $stringConverter
     * @param Validator                     $validator
     */
    public function __construct(
        ChainQueue $chainQueue,
        PluginCKEditorButtonGenerator $generator,
        Manager $extensionManager,
        StringConverter $stringConverter,
        Validator $validator
    ) {
        $this->chainQueue = $chainQueue;
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->stringConverter = $stringConverter;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:plugin:ckeditorbutton')
            ->setDescription($this->trans('commands.generate.plugin.ckeditorbutton.description'))
            ->setHelp($this->trans('commands.generate.plugin.ckeditorbutton.help'))
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
                $this->trans('commands.generate.plugin.ckeditorbutton.options.class')
            )
            ->addOption(
                'label',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.label')
            )
            ->addOption(
                'plugin-id',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.plugin-id')
            )
            ->addOption(
                'buttons',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.generate.plugin.ckeditorbutton.options.buttons')
            )->setAliases(['gpc']);
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
        $buttons = $input->getOption('buttons');

        $noInteraction = $input->getOption('no-interaction');
        // Parse nested data.
        if ($noInteraction) {
            $buttons = $this->explodeInlineArray($buttons);
        }

        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $label,
          'plugin_id' => $plugin_id,
          'buttons' => $buttons,
        ]);

        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery'], false);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $module = $this->getModuleOption();

        // --class option
        $class_name = $input->getOption('class');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.class'),
                'DefaultCKEditorButton',
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
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.label'),
                $this->stringConverter->camelCaseToHuman($class_name)
            );
            $input->setOption('label', $label);
        }

        // --plugin-id option
        $plugin_id = $input->getOption('plugin-id');
        if (!$plugin_id) {
            $plugin_id = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.plugin-id'),
                $this->stringConverter->createMachineName($label)
            );
            $input->setOption('plugin-id', $plugin_id);
        }

        $buttons = [];
        while (true) {
            $this->getIo()->newLine(2);
            $this->getIo()->comment($this->trans('commands.generate.plugin.ckeditorbutton.options.button-properties'));
            // --button-name option
            $buttonName = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.button-name'),
                $this->stringConverter->anyCaseToUcFirst($label)
            );

            $buttonLabel = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.button-label'),
                $label
            );

            $buttonIcon = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.button-icon-path'),
                drupal_get_path('module', $module) . '/js/plugin/'.$plugin_id.'/icons/'.$buttonName.'.png'
            );

            array_push(
                $buttons,
                [
                    'name' => $buttonName,
                    'label' => $buttonLabel,
                    'icon' => $buttonIcon,
                ]
            );

            if (!$this->getIo()->confirm(
                $this->trans('commands.generate.plugin.ckeditorbutton.questions.button-add'),
                true
            )
            ) {
                break;
            }
        }
        $input->setOption('buttons', $buttons);
    }
}
