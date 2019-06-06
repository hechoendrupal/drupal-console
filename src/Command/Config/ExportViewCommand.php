<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\ExportViewCommand.
 */

namespace Drupal\Console\Command\Config;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ExportTrait;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportViewCommand extends Command
{
    use ModuleTrait;
    use ExportTrait;

    protected $configExport;


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
     * @var Validator
     */
    protected $validator;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * ExportViewCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param CachedStorage              $configStorage
     * @param Manager                    $extensionManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CachedStorage $configStorage,
        Manager $extensionManager,
        Validator $validator,
        ChainQueue $chainQueue
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->configStorage = $configStorage;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('config:export:view')
            ->setDescription($this->trans('commands.config.export.view.description'))
            ->addOption(
                'module', null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.module')
            )
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.export.view.arguments.view-id')
            )
            ->addOption(
                'optional-config',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.view.options.optional-config')
            )
            ->addOption(
                'include-module-dependencies',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.config.export.view.options.include-module-dependencies')
            )
            ->addOption(
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
            ->setAliases(['cev']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --module option
        $this->getModuleOption();

        // view-id argument
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $views = $this->entityTypeManager->getStorage('view')->loadMultiple();

            $viewList = [];
            foreach ($views as $view) {
                $viewList[$view->get('id')] = $view->get('label');
            }

            $viewId = $this->getIo()->choiceNoList(
                $this->trans('commands.config.export.view.questions.view'),
                $viewList
            );
            $input->setArgument('view-id', $viewId);
        }

        $optionalConfig = $input->getOption('optional-config');
        if (!$optionalConfig) {
            $optionalConfig = $this->getIo()->confirm(
                $this->trans('commands.config.export.view.questions.optional-config'),
                true
            );
            $input->setOption('optional-config', $optionalConfig);
        }

        $includeModuleDependencies = $input->getOption('include-module-dependencies');
        if (!$includeModuleDependencies) {
            $includeModuleDependencies = $this->getIo()->confirm(
                $this->trans('commands.config.export.view.questions.include-module-dependencies'),
                true
            );
            $input->setOption('include-module-dependencies', $includeModuleDependencies);
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $this->validateModule($input->getOption('module'));
        $viewId = $input->getArgument('view-id');
        $optionalConfig = $input->getOption('optional-config');
        $includeModuleDependencies = $input->getOption('include-module-dependencies');
        $removeUuid = $input->getOption('remove-uuid');
        $removeHash = $input->getOption('remove-config-hash');

        $this->chainQueue->addCommand(
          'config:export:entity', [
            'entity-type' => 'view',
            'bundle' => [$viewId],
            '--module' => $module,
            '--optional-config' => $optionalConfig,
            '--remove-uuid' => $removeUuid,
            '--remove-config-hash' => $removeHash,
            '--include-module-dependencies' => $includeModuleDependencies
          ]
        );
    }
}
