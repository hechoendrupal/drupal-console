<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportEntityCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportEntityCommand extends Command {

    use ModuleTrait;
    use ExportTrait;

    const ALL = '-all-';

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

    /**
     * @var EntityTypeRepositoryInterface
     */
    protected $entityTypeRepository;

    protected $configExport;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * ExportContentTypeCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param CachedStorage $configStorage
     * @param Manager $extensionManager
     * @param Validator $validator
     * @param EntityTypeRepositoryInterface $entityTypeRepository
     * @param StorageInterface $storage

     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CachedStorage $configStorage,
        Manager $extensionManager,
        Validator $validator,
        EntityTypeRepositoryInterface  $entityTypeRepository,
        StorageInterface $storage
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->configStorage = $configStorage;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->entityTypeRepository = $entityTypeRepository;
        $this->storage = $storage;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this
            ->setName('config:export:entity')
            ->setDescription($this->trans('commands.config.export.entity.description'))
            ->addOption('module', NULL, InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module'))
            ->addArgument(
                'entity-type',
                InputArgument::REQUIRED,
                $this->trans('commands.config.export.entity.arguments.entity-type')
            )
            ->addArgument(
                'bundle',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                $this->trans('commands.config.export.entity.arguments.bundle')
            )->addOption(
                'optional-config',
                NULL,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.entity.options.optional-config')
            )->addOption(
                'remove-uuid',
                NULL,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.entity.options.remove-uuid')
            )->addOption(
                'remove-config-hash',
                NULL,
                InputOption::VALUE_NONE,
                $this->trans('commands.config.export.entity.options.remove-config-hash')
            )
            ->addOption(
                'include-module-dependencies',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.entity.options.include-module-dependencies')
            )
            ->setAliases(['cee']);

        $this->configExport = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output) {
        // --module option
        $this->getModuleOption();

        $entity_types = $this->entityTypeRepository->getEntityTypeLabels(true);
        ksort($entity_types['Configuration']);
        // --content-type argument
        $entityType = $input->getArgument('entity-type');
        if (!$entityType) {
            $entityType = $this->getIo()->choice(
                $this->trans('commands.config.export.entity.questions.content-type'),
                $entity_types['Configuration']
            );

            $input->setArgument('entity-type', $entityType);
        }

        // --bundle argument
        $bundle = $input->getArgument('bundle');
        if (!$bundle) {
            $bundles_entities = $this->entityTypeManager->getStorage($entityType)
                ->loadMultiple();
            $bundles = [ExportEntityCommand::ALL => $this->trans('commands.config.export.entity.questions.all')];
            $bundles_ids = [];
            foreach ($bundles_entities as $entity) {
                $bundles[$entity->id()] = $entity->label();
                $bundles_ids[] = $entity->id();
            }

            $bundle = $this->getIo()->choice(
                $this->trans('commands.config.export.entity.questions.bundle'),
                $bundles
            );

            if ($bundle == ExportEntityCommand::ALL) {
                $input->setArgument('bundle', $bundles_ids);
            } else {
                $input->setArgument('bundle', [$bundle]);
            }
        }

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $this->getIo()->confirm(
                $this->trans('commands.config.export.entity.questions.optional-config'),
                TRUE
            );

            $input->setOption('optional-config', $optionalConfig);
        }

        if (!$input->getOption('remove-uuid')) {
            $removeUuid = $this->getIo()->confirm(
                $this->trans('commands.config.export.entity.questions.remove-uuid'),
                TRUE
            );
            $input->setOption('remove-uuid', $removeUuid);
        }

        if (!$input->getOption('remove-config-hash')) {
            $removeHash = $this->getIo()->confirm(
                $this->trans('commands.config.export.entity.questions.remove-config-hash'),
                TRUE
            );
            $input->setOption('remove-config-hash', $removeHash);
        }

        $includeModuleDependencies = $input->getOption('include-module-dependencies');
        if (!$includeModuleDependencies) {
            $includeModuleDependencies = $this->getIo()->confirm(
                $this->trans('commands.config.export.entity.questions.include-module-dependencies'),
                true
            );
            $input->setOption('include-module-dependencies', $includeModuleDependencies);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $module = $this->validateModule($input->getOption('module'));
        $entityType = $input->getArgument('entity-type');
        $bundles = $input->getArgument('bundle');
        $optionalConfig = $input->getOption('optional-config');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');
        $includeModuleDependencies = $input->getOption('include-module-dependencies');

        foreach ($bundles as $bundle) {
            $bundleDefinition = $this->entityTypeManager->getDefinition($entityType);
            $bundleName = "{$bundleDefinition->getConfigPrefix()}.{$bundle}";

            $bundleNameConfig = $this->getConfiguration($bundleName,
                $removeUuid, $removeHash);

            if (empty($bundleNameConfig)) {
                throw new InvalidOptionException(sprintf('The bundle %s does not exist.',
                    $bundle));
            }

            $this->configExport[$bundleName] = [
                'data' => $bundleNameConfig,
                'optional' => $optionalConfig,
            ];

            $this->getBasefieldOverrideFields($bundle, $optionalConfig, $removeUuid, $removeHash);

            $this->getFields($bundle, $optionalConfig, $removeUuid, $removeHash);

            $this->getFormDisplays($bundle, $optionalConfig, $removeUuid,
                $removeHash);

            $this->getViewDisplays($bundle, $optionalConfig, $removeUuid,
                $removeHash);

            // Include module dependencies in export files if export is not optional
            if ($includeModuleDependencies) {
                if ($dependencies = $this->fetchDependencies($bundleNameConfig, 'module')) {
                    $this->exportModuleDependencies($module, $dependencies);
                }
            }

            $this->exportConfigToModule($module,
                sprintf(
                    $this->trans('commands.config.export.entity.messages.bundle-exported'),
                    $bundle
                ));
        }
    }
}
