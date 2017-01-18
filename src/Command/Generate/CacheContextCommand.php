<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\CacheContextCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Generator\CacheContextGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Utils\ChainQueue;

class CacheContextCommand extends Command
{
  use ModuleTrait;
  use ConfirmationTrait;
  use ContainerAwareCommandTrait;

  /**
   * @var CacheContextGenerator
   */
  protected $generator;

  /**
   * @var ChainQueue
   */
  protected $chainQueue;

  /**
   * CacheContextCommand constructor.
   *
   * @param CacheContextGenerator    $generator
   * @param ChainQueue               $chainQueue
   */
  public function __construct(
    CacheContextGenerator $generator,
    ChainQueue $chainQueue
  ) {
    $this->generator = $generator;
    $this->chainQueue = $chainQueue;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('generate:cache:context')
      ->setDescription($this->trans('commands.generate.cache.context.description'))
      ->setHelp($this->trans('commands.generate.cache.context.description'))
      ->addOption('module', null, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
      ->addOption(
        'cache_context',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.cache.context.questions.name')
      )
      ->addOption(
        'class',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.cache.context.questions.class')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new DrupalStyle($input, $output);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
    if (!$this->confirmGeneration($io)) {
      return;
    }

    $module = $input->getOption('module');
    $cache_context = $input->getOption('cache_context');
    $class = $input->getOption('class');

    $this->generator->generate($module, $cache_context, $class);

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $io = new DrupalStyle($input, $output);

    // --module option
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($io);
      $input->setOption('module', $module);
    }

    // --cache_context option
    $cache_context = $input->getOption('cache_context');
    if (!$cache_context) {
      $cache_context = $io->ask(
        $this->trans('ccommands.generate.cache.context.questions.name'),
        sprintf('%s.default', $module)
      );
      $input->setOption('name', $cache_context);
    }

    // --class option
    $class = $input->getOption('class');
    if (!$class) {
      $class = $io->ask(
        $this->trans('commands.generate.cache.context.questions.class'),
        'DefaultCacheContext'
      );
      $input->setOption('class', $class);
    }
  }
}
