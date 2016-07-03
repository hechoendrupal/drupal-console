<?php

namespace Drupal\Console\Command\Cache;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ContextDebugCommand.
 *
 * @package Drupal\Console\Command\Cache
 */
class ContextDebugCommand extends BaseCommand
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('cache:context:debug')
          ->setDescription($this->trans('commands.cache.context.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $contextManager = \Drupal::service('cache_contexts_manager');

        $tableHeader = [
          $this->trans('commands.cache.context.debug.messages.code'),
          $this->trans('commands.cache.context.debug.messages.label'),
          $this->trans('commands.cache.context.debug.messages.class'),
        ];

        $tableRows = [];

        foreach ($contextManager->getAll() as $code) {
            $context = \Drupal::service('cache_context.'.$code);

            $tableRows[] = [
              \Drupal\Component\Utility\SafeMarkup::checkPlain($code),
              $context->getLabel()->render(),
              get_class($context),
            ];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }
}
