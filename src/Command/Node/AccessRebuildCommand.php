<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Node\AccessRebuildCommand.
 */

namespace Drupal\Console\Command\Node;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class AccessRebuildCommand
 * @package Drupal\Console\Command\Node
 */
class AccessRebuildCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('node:access:rebuild')
            ->setDescription($this->trans('commands.node.access.rebuild.description'))
            ->addOption(
                'batch',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.node.access.rebuild.options.batch')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $io->newLine();
        $io->comment(
            $this->trans('commands.node.access.rebuild.messages.rebuild')
        );

        $batch = $input->getOption('batch');
        try {
            node_access_rebuild($batch);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return;
        }

        $needs_rebuild = $this->getDrupalService('state')->get('node.node_access_needs_rebuild') ? : false;
        if ($needs_rebuild) {
            $io->warning(
                $this->trans('commands.node.access.rebuild.messages.failed')
            );
        } else {
            $io->success(
                $this->trans('commands.node.access.rebuild.messages.completed')
            );
        }
    }
}
