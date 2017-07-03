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
use Drupal\Console\Core\Style\DrupalStyle;

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
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.entity.content.options.has-bundles')
        );

        $this->addOption(
            'is-translatable',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.entity.content.options.is-translatable')
        );

        $this->addOption(
            'revisionable',
            null,
            InputOption::VALUE_OPTIONAL,
            $this->trans('commands.generate.entity.content.options.revisionable')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        $io = new DrupalStyle($input, $output);

        // --has-bundles option.
        $this->interactBooleanQuestion($input, $io, 'has-bundles', false);

        // --is-translatable option
        $this->interactBooleanQuestion($input, $io, 'is-translatable', true);

        // --revisionable option
        $this->interactBooleanQuestion($input, $io, 'revisionable', true);
    }

    /**
     * Helper to ask for boolean option.
     *
     * @param InputInterface  $input
     * @param DrupalStyle     $io
     * @param string          $option
     * @param bool            $default
     */
    protected function interactBooleanQuestion(InputInterface $input, DrupalStyle $io, $option, $default = true) {
      // If no option flag has been set, we ask for manual input.
      if (!$input->hasOption($option)) {
        $set_value = $io->confirm(
          $this->trans('commands.generate.entity.content.questions.' . $option),
          (bool) $default
        );
      }
      else {
        $value = $input->getOption($option);
        // When the value of the option is "0" or "false" the option is disabled.
        if (isset($value) && $value === "0" || strtolower($value) === "false") {
          $set_value = FALSE;
        }
        // ... otherwise the option is enabled.
        else {
          $set_value = TRUE;
        }
      }

      $input->setOption($option, $set_value);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $entity_class = $input->getOption('entity-class');
        $entity_name = $input->getOption('entity-name');
        $label = $input->getOption('label');
        $has_bundles = $input->getOption('has-bundles');
        $base_path = $input->getOption('base-path');
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;
        $bundle_entity_name = $has_bundles ? $entity_name . '_type' : null;
        $is_translatable = $input->hasOption('is-translatable') ? $input->getOption('is-translatable') : true;
        $revisionable = $input->hasOption('revisionable') ? $input->getOption('revisionable') : false;

        $io = new DrupalStyle($input, $output);
        $generator = $this->generator;

        $generator->setIo($io);
        //@TODO:
        //$generator->setLearning($learning);

        $generator->generate($module, $entity_name, $entity_class, $label, $base_path, $is_translatable, $bundle_entity_name, $revisionable);

        if ($has_bundles) {
            $this->chainQueue->addCommand(
                'generate:entity:config', [
                '--module' => $module,
                '--entity-class' => $entity_class . 'Type',
                '--entity-name' => $entity_name . '_type',
                '--label' => $label . ' type',
                '--bundle-of' => $entity_name
                ]
            );
        }
    }
}
