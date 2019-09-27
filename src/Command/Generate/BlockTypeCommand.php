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
            ->addOption(
                'theme-region',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.block.type.options.theme-region')
            )
            ->addOption(
                'inputs',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.inputs')
            )
            ->addOption(
                'services',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->addOption(
                'twigtemplate',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.generate.block.type.options.twigtemplate')
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
        $services = $input->getOption('services');
        $theme_region = $input->getOption('theme-region');
        $inputs = $input->getOption('inputs');
        $noInteraction = $input->getOption('no-interaction');
        $twigTemplate = $input->getOption('twigtemplate');
        
        // Parse nested data.
        
        if ($noInteraction) {
            $inputs = $this->explodeInlineArray($inputs);
        }
        
        $theme = $this->configFactory->get('system.theme')->get('default');
        $themeRegions = \system_region_list($theme, REGIONS_VISIBLE);

        if (!empty($theme_region) && !isset($themeRegions[$theme_region])) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.generate.block.type.messages.invalid-theme-region'),
                    $theme_region
                )
            );

            return 1;
        }

        // @see use Drupal\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);
        
        $theme_region = true;
        
        $this->generator->generate([
          'module' => $module,
          'class_name' => $class_name,
          'label' => $block_label,
          'description' => $block_description,
          'block_id' => $block_id,
          'services' => $build_services,
          'inputs' => $inputs,
          'twig_template' => $twigTemplate,
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
                'DefaultBlock',
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
        // --theme-region option
        $themeRegion = $input->getOption('theme-region');

        if (!$themeRegion) {
            $theme = $this->configFactory->get('system.theme')->get('default');
            $themeRegions = \system_region_list($theme, REGIONS_VISIBLE);
            $themeRegionOptions = [];
            foreach ($themeRegions as $key => $region) {
                $themeRegionOptions[$key] = $region->render();
            }
            $themeRegion = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.block.type.questions.theme-region'),
                $themeRegionOptions,
                '',
                true
            );
            $themeRegion = array_search($themeRegion, $themeRegions);
            $input->setOption('theme-region', $themeRegion);
        }

        // --services option
        // @see Drupal\Console\Command\Shared\ServicesTrait::servicesQuestion
        $services = $this->servicesQuestion();
        $input->setOption('services', $services);

        $output->writeln($this->trans('commands.generate.block.type.messages.inputs'));

        // --inputs option
        $inputs = $input->getOption('inputs');
        if (!$inputs) {
            // @see \Drupal\Console\Command\Shared\FormTrait::formQuestion
            $inputs = $this->formQuestion();
            $input->setOption('inputs', $inputs);
        } else {
            $inputs = $this->explodeInlineArray($inputs);
        }
        $input->setOption('inputs', $inputs);

        $twigtemplate = $input->getOption('twigtemplate');
        if (!$twigtemplate) {
            $twigtemplate = $this->getIo()->confirm(
                $this->trans('commands.generate.block.type.questions.twigtemplate'),
                false
            );
            $input->setOption('twigtemplate', $twigtemplate);
        }
    }
}
