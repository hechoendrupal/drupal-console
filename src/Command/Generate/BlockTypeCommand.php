<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\BlockTypeCommand.
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
use Drupal\Console\Generator\BlockTypeGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block\Entity\Block;
use Drupal\block_content\BlockContentTypeInterface;
use Drupal\block_content\Entity\BlockContentType;

class BlockTypeCommand extends ContainerAwareCommand
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
     * @var BlockTypeGenerator
     */
    protected $generator;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

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
     * @param BlockTypeGenerator          $generator
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param Manager                     $extensionManager
     * @param Validator                   $validator
     * @param StringConverter             $stringConverter
     * @param ElementInfoManagerInterface $elementInfoManager
     */
    public function __construct(
        ConfigFactory $configFactory,
        ChainQueue $chainQueue,
        BlockTypeGenerator $generator,
        EntityTypeManagerInterface $entityTypeManager,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        ElementInfoManagerInterface $elementInfoManager
    ) {
        $this->configFactory = $configFactory;
        $this->chainQueue = $chainQueue;
        $this->generator = $generator;
        $this->entityTypeManager = $entityTypeManager;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->elementInfoManager = $elementInfoManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate:block:type')
            ->setDescription($this->trans('commands.generate.block.type.description'))
            ->setHelp($this->trans('commands.generate.block.type.help'))
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
                $this->trans('commands.generate.block.type.options.class')
            )
            ->addOption(
                'block-label',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.block.type.options.block-label')
            )
            ->addOption(
                'block-description',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.block.type.options.block-description')
            )
            ->addOption(
                'block-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.block.type.options.block-id')
            )
            
            ->setAliases(['gbt']);
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
        $block_label = $input->getOption('block-label');
        $block_description = $input->getOption('block-description');
        $block_id = $input->getOption('block-id');
        
        $theme_region = true;
        
        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $block_label,
          'description' => $block_description,
          'block_id' => $block_id,
        ]);
        
        $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

        if ($theme_region) {
            $block_content_type = BlockContentType::create([
              'id' => $block_id,
              'label' => $block_label,
              'description' => $block_description,

            ]);
            $block_content_type->save();

            $block_content = BlockContent::create([
              'info' => $block_label,
              'type' => $block_id,
              'body' => [
              'value' => "<h1>Block's body</h1>",
                'format' => 'full_html',
               ],
            ]);

            $block_content->save();
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --class option
        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.block.type.questions.class'),
                'DefaultBlockContentType',
                function ($class) {
                    return $this->validator->validateClassName($class);
                }
            );
            $input->setOption('class', $class);
        }

        // --block-label option
        $block_label = $input->getOption('block-label');
        if (!$block_label) {
            $block_label = $this->getIo()->ask(
                $this->trans('commands.generate.block.type.questions.block-label'),
                $this->stringConverter->camelCaseToHuman($class)
            );
            $input->setOption('block-label', $block_label);
        }

        // --block-id option
        $blockId = $input->getOption('block-id');
        if (!$blockId) {
            $blockId = $this->getIo()->ask(
                $this->trans('commands.generate.block.type.questions.block-id'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('block-id', $blockId);
        }
        // --block-description option
        $blockDesc = $input->getOption('block-description');
        if (!$blockDesc) {
            $blockDesc = $this->getIo()->ask(
                $this->trans('commands.generate.block.type.questions.block-description'),
                $this->stringConverter->camelCaseToUnderscore($class)
            );
            $input->setOption('block-description', $blockDesc);
        }
    }
}
