<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Generator\EntityContentGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Validator;

class EntityContentCommand extends EntityCommand
{
    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var EntityContentGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * EntityContentCommand constructor.
     *
     * @param ChainQueue             $chainQueue
     * @param EntityContentGenerator $generator
     * @param StringConverter        $stringConverter
     * @param Manager                $extensionManager
     * @param Validator              $validator
     */
    public function __construct(
        ChainQueue $chainQueue,
        EntityContentGenerator $generator,
        StringConverter $stringConverter,
        Manager $extensionManager,
        Validator $validator
    ) {
        $this->chainQueue = $chainQueue;
        $this->generator = $generator;
        $this->stringConverter = $stringConverter;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();
        $this->addOption(
            'has-bundles',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.has-bundles')
        );

        $this->addOption(
            'is-translatable',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.is-translatable')
        );

        $this->addOption(
            'revisionable',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.revisionable')
        );

        $this->addOption(
            'has-forms',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.has-forms')
        );

        $this->addOption(
            'has-owner',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.has-owner')
        );

        $this->addOption(
            'has-bundle-permissions',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.has-bundle-permissions')
        );

        $this->setAliases(['geco']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        // --bundle-of option
        $bundle_of = $input->getOption('has-bundles');
        if (!$bundle_of) {
            $bundle_of = $this->getIo()->confirm(
                $this->trans('commands.generate.entity.content.questions.has-bundles'),
                false
            );
            $input->setOption('has-bundles', $bundle_of);
        }

        // --is-translatable option
        $is_translatable = $this->getIo()->confirm(
            $this->trans('commands.generate.entity.content.questions.is-translatable'),
            true
        );
        $input->setOption('is-translatable', $is_translatable);

        // --revisionable option
        $revisionable = $this->getIo()->confirm(
            $this->trans('commands.generate.entity.content.questions.revisionable'),
            true
        );
        $input->setOption('revisionable', $revisionable);

        // --has-forms option
        $has_forms = $this->getIo()->confirm(
            $this->trans('commands.generate.entity.content.questions.has-forms'),
            true
        );
        $input->setOption('has-forms', $has_forms);

        // --has-owner option
        $has_owner = $this->getIo()->confirm(
            $this->trans('commands.generate.entity.content.questions.has-owner'),
            true
        );
        $input->setOption('has-owner', $has_owner);

        // --has-bundle-permissions
        if($bundle_of){
          $has_bundle_permissions = $this->getIo()->confirm(
            $this->trans('commands.generate.entity.content.questions.has-bundle-permissions'),
            true
          );
          $input->setOption('has-bundle-permissions', $has_bundle_permissions);
        }
        else {
          $input->setOption('has-bundle-permissions', false);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $this->validateModule($input->getOption('module'));
        $entity_class = $input->getOption('entity-class')?:'DefaultEntity';
        $entity_name = $this->validator->validateMachineName($input->getOption('entity-name'))?:'default_entity';
        $label = $input->getOption('label')?:'Default Entity';
        $has_bundles = $input->getOption('has-bundles')?:false;
        $base_path = $input->getOption('base-path')?:'/admin/structure';
        $learning = $input->getOption('learning')?:false;
        $bundle_entity_type = $has_bundles ? $entity_name . '_type' : null;
        $is_translatable = $input->getOption('is-translatable');
        $revisionable = $input->getOption('revisionable');
        $has_forms = $input->getOption('has-forms');
        $has_owner = $input->getOption('has-owner');
        $has_bundle_permissions = $input->getOption('has-bundle-permissions');

        $generator = $this->generator;

        $generator->setIo($this->getIo());
        //@TODO:
        //$generator->setLearning($learning);

        $generator->generate([
            'module' => $module,
            'entity_name' => $entity_name,
            'entity_class' => $entity_class,
            'label' => $label,
            'bundle_entity_type' => $bundle_entity_type,
            'base_path' => $base_path,
            'is_translatable' => $is_translatable,
            'revisionable' => $revisionable,
            'has_forms' => $has_forms,
            'has_owner' => $has_owner,
            'has_bundle_permissions' => $has_bundle_permissions,
        ]);

        if ($has_bundles) {
            $this->chainQueue->addCommand(
                'generate:entity:config', [
                '--module' => $module,
                '--entity-class' => $entity_class . 'Type',
                '--entity-name' => $entity_name . '_type',
                '--label' => $label . ' type',
                '--bundle-of' => $entity_name,
                '--no-interaction'
                ]
            );
        }
    }
}
