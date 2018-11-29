<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportContentTypeCommand.
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

class ExportContentTypeCommand extends Command
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
     * ExportContentTypeCommand constructor.
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
            ->setName('config:export:content:type')
            ->setDescription($this->trans('commands.config.export.content.type.description'))
            ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
            ->addArgument(
                'content-type',
                InputArgument::REQUIRED,
                $this->trans('commands.config.export.content.type.arguments.content-type')
            )->addOption(
                'optional-config',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.content.type.options.optional-config')
            )->addOption(
                'remove-uuid',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.content.type.options.remove-uuid')
            )->addOption(
                'remove-config-hash',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.content.type.options.remove-config-hash')
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

        // --content-type argument
        $contentType = $input->getArgument('content-type');
        if (!$contentType) {
            $bundles_entities = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
            $bundles = [];
            foreach ($bundles_entities as $entity) {
                $bundles[$entity->id()] = $entity->label();
            }

            $contentType = $this->getIo()->choice(
                $this->trans('commands.config.export.content.type.questions.content-type'),
                $bundles
            );
        }
        $input->setArgument('content-type', $contentType);

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $this->getIo()->confirm(
                $this->trans('commands.config.export.content.type.questions.optional-config'),
                true
            );
        }
        $input->setOption('optional-config', $optionalConfig);


        if (!$input->getOption('remove-uuid')) {
            $removeUuid = $this->getIo()->confirm(
                $this->trans('commands.config.export.content.type.questions.remove-uuid'),
                true
            );
            $input->setOption('remove-uuid', $removeUuid);
        }
        if (!$input->getOption('remove-config-hash')) {
            $removeHash = $this->getIo()->confirm(
                $this->trans('commands.config.export.content.type.questions.remove-config-hash'),
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
        $module = $this->validateModule($input->getOption('module'));
        $contentType = $input->getArgument('content-type');
        $optionalConfig = $input->getOption('optional-config');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');

        $contentTypeDefinition = $this->entityTypeManager->getDefinition('node_type');
        $contentTypeName = "{$contentTypeDefinition->getConfigPrefix()}.{$contentType}";


        $contentTypeNameConfig = $this->getConfiguration($contentTypeName, $removeUuid, $removeHash);

        if (empty($contentTypeNameConfig)) {
            throw new InvalidOptionException(sprintf('The content type %s does not exist.', $contentType));
        }

        $this->configExport[$contentTypeName] = ['data' => $contentTypeNameConfig, 'optional' => $optionalConfig];

        $this->getFields($input);

        $this->getFormDisplays($input);

        $this->getViewDisplays($input);

        $this->exportConfigToModule($module, $this->trans('commands.config.export.content.type.messages.content-type-exported'));
    }

    protected function getFields($input)
    {
        $this->extractConfig('field_config', $input);
    }

    protected function getFormDisplays($input)
    {
        $this->extractConfig('entity_form_display', $input);
    }

    protected function getViewDisplays($input)
    {
        $this->extractConfig('entity_view_display', $input);
    }

    protected function extractConfig($name, $input)
    {
        $contentType = $input->getArgument('content-type');
        $optionalConfig = $input->getOption('optional-config');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');

        $definition = $this->entityTypeManager->getDefinition($name);
        $storage = $this->entityTypeManager->getStorage($name);
        foreach ($storage->loadMultiple() as $entity) {
            $configName = "{$definition->getConfigPrefix()}.{$entity->id()}";
            $config = $this->getConfiguration($configName, $removeUuid, $removeHash);
            // Only select items related to content type.
            if ($config['bundle'] == $contentType) {
                $this->configExport[$configName] = ['data' => $config, 'optional' => $optionalConfig];
                // Include dependencies in export files
                if ($dependencies = $this->fetchDependencies($config, 'config')) {
                    $this->resolveDependencies($dependencies, $optionalConfig, $removeUuid, $removeHash);
                }
            }
        }
    }
}
