<?php

namespace Drupal\Console\Command\Generate;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Utils\Validator;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class PluginQueueWorkerCommand.
 *
 * @package Drupal\Console\Command\Generate
 */
class PluginQueueWorkerCommand extends Command {

  use ModuleTrait;
  use ConfirmationTrait;

  /**
   * Drupal\Console\Core\Generator\GeneratorInterface definition.
   *
   * @var \Drupal\Console\Core\Generator\GeneratorInterface
   */
  protected $generator;

  /**
   * Validator.
   *
   * @var \Drupal\Console\Utils\Validator
   */
  protected $validator;

  /**
   * String converter.
   *
   * @var \Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * Chain queue.
   *
   * @var \Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;


  /**
   * PluginQueueWorkerCommand constructor.
   *
   * @param \Drupal\Console\Core\Generator\GeneratorInterface $queue_generator
   *   Queue Generator.
   * @param \Drupal\Console\Utils\Validator $validator
   *   Validator.
   * @param \Drupal\Console\Core\Utils\StringConverter $stringConverter
   *   String Converter.
   * @param \Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   Chain queue.
   */
  public function __construct(
    GeneratorInterface $queue_generator,
    Validator $validator,
    StringConverter $stringConverter,
    ChainQueue $chainQueue
  ) {
    $this->generator = $queue_generator;
    $this->validator = $validator;
    $this->stringConverter = $stringConverter;
    $this->chainQueue = $chainQueue;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:plugin:queue')
      ->setDescription($this->trans('commands.generate.plugin.queue.description'))
      ->setHelp($this->trans('commands.generate.plugin.queue.help'))
      ->addOption(
          'module',
          NULL,
          InputOption::VALUE_REQUIRED,
          $this->trans('commands.generate.plugin.queue.options.module')
      )
      ->addOption(
          'class',
          NULL,
          InputOption::VALUE_REQUIRED,
          $this->trans('commands.generate.plugin.queue.options.class')
      )
      ->addOption(
          'plugin-id',
          NULL,
          InputOption::VALUE_REQUIRED,
          $this->trans('commands.generate.plugin.queue.options.plugin-id')
      )
      ->addOption(
          'cron-time',
          NULL,
          InputOption::VALUE_REQUIRED,
          $this->trans('commands.generate.plugin.queue.options.cron-time')
      )
      ->addOption(
          'label',
          NULL,
          InputOption::VALUE_REQUIRED,
          $this->trans('commands.generate.plugin.queue.options.label')
      )
      ->setAliases(['gpqueue']);
  }


  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    // --module option.
    $this->getModuleOption();

    // --class option.
    $queue_class = $input->getOption('class');
    if (!$queue_class) {
      $queue_class = $this->getIo()->ask(
            $this->trans('commands.generate.plugin.queue.questions.class'),
            'ExampleQueue',
            function ($queue_class) {
              return $this->validator->validateClassName($queue_class);
            }
        );
      $input->setOption('class', $queue_class);
    }

    // --plugin-id option.
    $plugin_id = $input->getOption('plugin-id');
    if (!$plugin_id) {
      $plugin_id = $this->getIo()->ask(
          $this->trans('commands.generate.plugin.queue.questions.plugin-id'),
          'example_plugin_id',
          function ($plugin_id) {
            return $this->stringConverter->camelCaseToUnderscore($plugin_id);
          }
      );
      $input->setOption('plugin-id', $plugin_id);
    }

    // --cron-time option.
    $cron_time = $input->getOption('cron-time');
    if (!$cron_time) {
      $cron_time = $this->getIo()->ask(
          $this->trans('commands.generate.plugin.queue.questions.cron-time'),
          30
      );
      $input->setOption('cron-time', $cron_time);
    }

    // --label option.
    $label = $input->getOption('label');
    if (!$label) {
      $label = $this->getIo()->ask(
          $this->trans('commands.generate.plugin.queue.questions.label'),
          'Queue description.'
      );
      $input->setOption('label', $label);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }
    $module = $input->getOption('module');
    $queue_class = $input->getOption('class');
    $plugin_id = $input->getOption('plugin-id');
    $cron_time = $input->getOption('cron-time');
    $label = $input->getOption('label');
    $this->generator->generate([
      'module' => $module,
      'class_name' => $queue_class,
      'plugin_id' => $plugin_id,
      'cron_time' => $cron_time,
      'label' => $label,
    ]);

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

    return 0;
  }

}
