<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportParagraphTypeCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Extension\Manager;

class ExportParagraphTypeCommand extends Command
{
    use ModuleTrait;
    use ExportTrait;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var Manager
     */
    protected $extensionManager;

    protected $configExport;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * ExportParagraphTypeCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param CachedStorage              $configStorage
     * @param Manager                    $extensionManager
     * @param Validator                  $validator
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CachedStorage $configStorage,
        Manager $extensionManager,
        Validator $validator
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->configStorage = $configStorage;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:export:paragraph:type')
            ->setDescription($this->trans('commands.config.export.paragraph.type.description'))
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument(
                'paragraph-type',
                InputArgument::REQUIRED,
                $this->trans('commands.config.export.paragraph.type.arguments.paragraph-type')
            )->addOption(
                'optional-config',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.paragraph.type.options.optional-config')
            )->addOption(
                'remove-uuid',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.paragraph.type.options.remove-uuid')
            )->addOption(
                'remove-config-hash',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.paragraph.type.options.remove-config-hash')
            )
            ->setAliases(['cect']);

        $this->configExport = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // --paragraph-type argument
        $paragraphType = $input->getArgument('paragraph-type');
        if (!$paragraphType) {
            $bundles_entities = $this->entityTypeManager->getStorage('paragraphs_type')->loadMultiple();
            $bundles = [];
            foreach ($bundles_entities as $entity) {
                $bundles[$entity->id()] = $entity->label();
            }

            $paragraphType = $this->getIo()->choice(
                $this->trans('commands.config.export.paragraph.type.questions.paragraph-type'),
                $bundles
            );
        }
        $input->setArgument('paragraph-type', $paragraphType);

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $this->getIo()->confirm(
                $this->trans('commands.config.export.paragraph.type.questions.optional-config'),
                true
            );
        }
        $input->setOption('optional-config', $optionalConfig);


        if (!$input->getOption('remove-uuid')) {
            $removeUuid = $this->getIo()->confirm(
                $this->trans('commands.config.export.paragraph.type.questions.remove-uuid'),
                true
            );
            $input->setOption('remove-uuid', $removeUuid);
        }
        if (!$input->getOption('remove-config-hash')) {
            $removeHash = $this->getIo()->confirm(
                $this->trans('commands.config.export.paragraph.type.questions.remove-config-hash'),
                true
            );
            $input->setOption('remove-config-hash', $removeHash);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $paragraphType = $input->getArgument('paragraph-type');
        $optionalConfig = $input->getOption('optional-config');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');

        $paragraphTypeDefinition = $this->entityTypeManager->getDefinition('paragraphs_type');
        $paragraphTypeName = $paragraphTypeDefinition->getConfigPrefix() . '.' . $paragraphType;

        $paragraphTypeNameConfig = $this->getConfiguration($paragraphTypeName, $removeUuid, $removeHash);

        if (empty($paragraphTypeNameConfig)) {
            throw new InvalidOptionException(sprintf('The paragraph type %s does not exist.', $paragraphType));
        }

        $this->configExport[$paragraphTypeName] = ['data' => $paragraphTypeNameConfig, 'optional' => $optionalConfig];

        $this->getFields($paragraphType, $optionalConfig, $removeUuid, $removeHash);

        $this->getFormDisplays($paragraphType, $optionalConfig, $removeUuid, $removeHash);

        $this->getViewDisplays($paragraphType, $optionalConfig, $removeUuid, $removeHash);

        $this->exportConfigToModule($module, $this->trans('commands.config.export.paragraph.type.messages.paragraph-type-exported'));
    }

    protected function getFields($paragraphType, $optional = false, $removeUuid = false, $removeHash = false)
    {
        $fields_definition = $this->entityTypeManager->getDefinition('field_config');

        $fields_storage = $this->entityTypeManager->getStorage('field_config');
        foreach ($fields_storage->loadMultiple() as $field) {
            $field_name = $fields_definition->getConfigPrefix() . '.' . $field->id();
            $field_name_config = $this->getConfiguration($field_name, $removeUuid, $removeHash);

            // Only select fields related with paragraph type
            if ($field_name_config['bundle'] == $paragraphType) {
                $this->configExport[$field_name] = ['data' => $field_name_config, 'optional' => $optional];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($field_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function getFormDisplays($paragraphType, $optional = false, $removeUuid = false, $removeHash = false)
    {
        $form_display_definition = $this->entityTypeManager->getDefinition('entity_form_display');
        $form_display_storage = $this->entityTypeManager->getStorage('entity_form_display');
        foreach ($form_display_storage->loadMultiple() as $form_display) {
            $form_display_name = $form_display_definition->getConfigPrefix() . '.' . $form_display->id();
            $form_display_name_config = $this->getConfiguration($form_display_name, $removeUuid, $removeHash);
            // Only select fields related with paragraph type
            if ($form_display_name_config['bundle'] == $paragraphType) {
                $this->configExport[$form_display_name] = ['data' => $form_display_name_config, 'optional' => $optional];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($form_display_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }

    protected function getViewDisplays($paragraphType, $optional = false, $removeUuid = false, $removeHash = false)
    {
        $view_display_definition = $this->entityTypeManager->getDefinition('entity_view_display');
        $view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
        foreach ($view_display_storage->loadMultiple() as $view_display) {
            $view_display_name = $view_display_definition->getConfigPrefix() . '.' . $view_display->id();
            $view_display_name_config = $this->getConfiguration($view_display_name, $removeUuid, $removeHash);
            // Only select fields related with paragraph type
            if ($view_display_name_config['bundle'] == $paragraphType) {
                $this->configExport[$view_display_name] = ['data' => $view_display_name_config, 'optional' => $optional];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($view_display_name_config, 'config')) {
                    $this->resolveDependencies($dependencies, $optional);
                }
            }
        }
    }
}
