<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportContentTypeCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;

class ExportContentTypeCommand extends Command
{
    use ModuleTrait;

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
     * ExportContentTypeCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param Validator $validator
     * @param ChainQueue $chainQueue
     */
    public function __construct(
        Manager $extensionManager,
        EntityTypeManagerInterface $entityTypeManager,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->extensionManager = $extensionManager;
        $this->entityTypeManager = $entityTypeManager;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
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

        $this->chainQueue->addCommand(
            'config:export:entity', [
                'entity-type' => 'node_type',
                'bundle' => $contentType,
                '--module' => $module,
                '--optional-config' => $optionalConfig,
                '--remove-uuid' => $removeUuid,
                '--remove-config-hash' => $removeHash
            ]
        );
    }
}
