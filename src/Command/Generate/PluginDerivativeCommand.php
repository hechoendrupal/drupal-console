<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\PluginDerivativeCommand.
 */

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Command\Shared\ArrayInputTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\PluginDerivativeGenerator;

class PluginDerivativeCommand extends ContainerAwareCommand
{
    use ArrayInputTrait;
    use ServicesTrait;
    use ModuleTrait;
    use FormTrait;
    use ConfirmationTrait;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var PluginDerivativeGenerator
     */
    protected $generator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var ElementInfoManagerInterface
     */
    protected $elementInfoManager;

    /**
     * BlockTypeCommand constructor.
     *
     * @param ConfigFactory               $configFactory
     * @param ChainQueue                  $chainQueue
     * @param PluginDerivativeGenerator   $generator
     * @param Manager                     $extensionManager
     * @param Validator                   $validator
     * @param StringConverter             $stringConverter
     * @param ElementInfoManagerInterface $elementInfoManager
     */
    public function __construct(
        ConfigFactory $configFactory,
        ChainQueue $chainQueue,
        PluginDerivativeGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->configFactory    = $configFactory;
        $this->chainQueue       = $chainQueue;
        $this->generator        = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator        = $validator;
        $this->stringConverter  = $stringConverter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:plugin:derivative')
            ->setDescription($this->trans('commands.generate.derivative.description'))
            ->setHelp($this->trans('commands.generate.plugin.derivative.help'))
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
                $this->trans('commands.generate.plugin.derivative.options.class')
            )
            ->addOption(
                'block_label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.derivative.options.block_label')
            )
            ->addOption(
                'block_description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.derivative.options.block_description')
            )
            ->addOption(
                'block_id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.plugin.derivative.options.block_id')
            )
            ->setAliases(['gpd']);
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
        $block_label = $input->getOption('block_label');
        $block_description = $input->getOption('block_description');
        $block_id = $input->getOption('block_id');
        
        $theme_region = true;
        
        $this->generator->generate(
            [
            'module' => $module,
            'class' => $class_name,
            'block_label' => $block_label,
            'block_description' => $block_description,
            'block_id' => $block_id,
            ]
        );
        
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.derivative.questions.class'),
                $this->trans('commands.generate.plugin.derivative.suggestions.class'),
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --block_label option
        $block_label = $input->getOption('block_label');
        if (!$block_label) {
            $block_label = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.derivative.questions.block_label'),
                $this->trans('commands.generate.plugin.derivative.suggestions.block_label')
            );
            $input->setOption('block_label', $block_label);
        }

        // --block_id option
        $blockId = $input->getOption('block_id');
        if (!$blockId) {
            $blockId = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.derivative.questions.block_id'),
                $this->stringConverter->camelCaseToUnderscore($blockId)
            );
            $input->setOption('block_id', $blockId);
        }
        // --block_description option
        $blockDesc = $input->getOption('block_description');
        if (!$blockDesc) {
            $blockDesc = $this->getIo()->ask(
                $this->trans('commands.generate.plugin.derivative.questions.block_description'),
                $this->trans('commands.generate.plugin.derivative.suggestions.block_description')
            );
            $input->setOption('block_description', $blockDesc);
        }
    }
}
