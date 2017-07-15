<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\CacheContextCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class CacheContextCommand.
 *
 * @package Drupal\Console\Command\Debug
 */
class CacheContextCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:cache:context')
            ->setDescription($this->trans('commands.debug.cache.context.description'))
            ->setAliases(['dcc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $contextManager = $this->get('cache_contexts_manager');

        $tableHeader = [
            $this->trans('commands.debug.cache.context.messages.code'),
            $this->trans('commands.debug.cache.context.messages.label'),
            $this->trans('commands.debug.cache.context.messages.class'),
        ];

        $tableRows = [];

        foreach ($contextManager->getAll() as $code) {
            $context = $this->get('cache_context.'.$code);
            $tableRows[] = [
                $code,
                $context->getLabel()->render(),
                get_class($context),
            ];
        }

        $io->table($tableHeader, $tableRows, 'compact');

        return 0;
    }
}
