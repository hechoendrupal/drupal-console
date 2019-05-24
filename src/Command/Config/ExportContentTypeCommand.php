<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportContentTypeCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Drupal\Core\Config\CachedStorage;

class ExportContentTypeCommand extends Command
{
    use ModuleTrait;
    use ExportTrait;

    const ALL = '-all-';

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * ExportContentTypeCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param Validator $validator
     * @param ChainQueue $chainQueue
     * @param CachedStorage $configStorage,
     */
    public function __construct(
        Manager $extensionManager,
        EntityTypeManagerInterface $entityTypeManager,
        Validator $validator,
        ChainQueue $chainQueue,
        CachedStorage $configStorage

    ) {
        $this->extensionManager = $extensionManager;
        $this->entityTypeManager = $entityTypeManager;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        $this->configStorage = $configStorage;
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
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
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
            ->addOption(
              'include-module-dependencies',
              null,
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.config.export.content.type.options.include-module-dependencies')
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
            $bundles = [ExportContentTypeCommand::ALL => $this->trans('commands.config.export.content.type.questions.all')];
            $bundles_ids = [];

            foreach ($bundles_entities as $entity) {
                $bundles[$entity->id()] = $entity->label();
                $bundles_ids[] = $entity->id();
            }

            if (!$contentType) {
              $contentType = $this->getIo()->choice(
                  $this->trans('commands.config.export.content.type.questions.content-type'),
                  $bundles
              );
            }

            if ($contentType == ExportContentTypeCommand::ALL) {
              $input->setArgument('content-type', $bundles_ids);
            }else{
              $input->setArgument('content-type', [$contentType]);
            }
        }

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $this->getIo()->confirm(
                $this->trans('commands.config.export.content.type.questions.optional-config'),
                true
            );
            $input->setOption('optional-config', $optionalConfig);
        }

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

        $includeModuleDependencies = $input->getOption('include-module-dependencies');
        if (!$includeModuleDependencies) {
          $includeModuleDependencies = $this->getIo()->confirm(
            $this->trans('commands.config.export.content.type.questions.include-module-dependencies'),
            true
          );
          $input->setOption('include-module-dependencies', $includeModuleDependencies);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $contentTypes = $input->getArgument('content-type');
        $optionalConfig = $input->getOption('optional-config');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');
        $includeModuleDependencies = $input->getOption('include-module-dependencies');

        $this->chainQueue->addCommand(
          'config:export:entity', [
            'entity-type' => 'node_type',
            'bundle' => $contentTypes,
            '--module' => $module,
            '--optional-config' => $optionalConfig,
            '--remove-uuid' => $removeUuid,
            '--remove-config-hash' => $removeHash,
            '--include-module-dependencies' => $includeModuleDependencies
          ]
        );
    }
}
