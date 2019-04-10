<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportContentTypeCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Extension\Manager;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportEntityCommand extends Command {

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
   * ExportContentTypeCommand constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param CachedStorage $configStorage
   * @param Manager $extensionManager
   * @param Validator $validator
   * @param EntityTypeRepositoryInterface $entityTypeRepository
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CachedStorage $configStorage,
    Manager $extensionManager,
    Validator $validator,
    EntityTypeRepositoryInterface  $entityTypeRepository
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configStorage = $configStorage;
    $this->extensionManager = $extensionManager;
    $this->validator = $validator;
    $this->entityTypeRepository = $entityTypeRepository;
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
        InputArgument::REQUIRED,
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
    }
    $input->setArgument('entity-type', $entityType);

    // --bundle argument
    $bundle = $input->getArgument('bundle');
    if (!$bundle) {
      $bundles_entities = $this->entityTypeManager->getStorage($entityType)
        ->loadMultiple();
      foreach ($bundles_entities as $entity) {
        $bundles[$entity->id()] = $entity->label();
      }

      $bundle = $this->getIo()->choice(
        $this->trans('commands.config.export.entity.questions.bundle'),
        $bundles
      );
    }
    $input->setArgument('bundle', $bundle);

    $optionalConfig = $input->getOption('optional-config');
    if (!$optionalConfig) {
      $optionalConfig = $this->getIo()->confirm(
        $this->trans('commands.config.export.entity.questions.optional-config'),
        TRUE
      );
    }
    $input->setOption('optional-config', $optionalConfig);


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
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module = $this->validateModule($input->getOption('module'));
    $entityType = $input->getArgument('entity-type');
    $bundle = $input->getArgument('bundle');
    $optionalConfig = $input->getOption('optional-config');
    $removeUuid = $input->getOption('remove-uuid');
    $removeHash = $input->getOption('remove-config-hash');

    $bundleDefinition = $this->entityTypeManager->getDefinition($entityType);
    $bundleName = $bundleDefinition->getConfigPrefix() . '.' . $bundle;

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

    $this->getFields($bundle, $optionalConfig, $removeUuid, $removeHash);

    $this->getFormDisplays($bundle, $optionalConfig, $removeUuid,
      $removeHash);

    $this->getViewDisplays($bundle, $optionalConfig, $removeUuid,
      $removeHash);

    $this->exportConfigToModule($module,
      $this->trans('commands.config.export.entity.messages.content-type-exported'));
  }
}
