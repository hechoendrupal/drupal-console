<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\NodesCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\CreateTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class NodesCommand
 * @package Drupal\Console\Command\Generate
 */
class NodesCommand extends Command
{
    use CreateTrait;
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:nodes')
            ->setDescription($this->trans('commands.create.nodes.description'))
            ->addArgument(
                'content-types',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.nodes.arguments.content-types')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.limit')
            )
            ->addOption(
                'title-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.title-words')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.options.time-range')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $contentTypes = $input->getArgument('content-types');
        if (!$contentTypes) {
            $bundles = $this->getApplication()->getDrupalApi()->getBundles();
            $contentTypes = $io->choice(
                $this->trans('commands.create.nodes.questions.content-type'),
                array_values($bundles),
                null,
                true
            );

            $contentTypes = array_map(
                function ($contentType) use ($bundles) {
                    return array_search($contentType, $bundles);
                },
                $contentTypes
            );

            $input->setArgument('content-types', $contentTypes);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.nodes.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $titleWords = $input->getOption('title-words');
        if (!$titleWords) {
            $titleWords = $io->ask(
                $this->trans('commands.create.nodes.questions.title-words'),
                5
            );

            $input->setOption('title-words', $titleWords);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
                $this->trans('commands.create.nodes.questions.time-range'),
                array_values($timeRanges)
            );

            $input->setOption('time-range',  array_search($timeRange, $timeRanges));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $createNodes = $this->getApplication()->getDrupalApi()->getCreateNodes();

        $contentTypes = $input->getArgument('content-types');
        $limit = $input->getOption('limit')?:25;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:31536000;

        if (!$contentTypes) {
            $contentTypes = array_keys($this->getApplication()->getDrupalApi()->getBundles());
        }

        $nodes = $createNodes->createNode(
            $contentTypes,
            $limit,
            $titleWords,
            $timeRange
        );

        $tableHeader = [
          $this->trans('commands.create.nodes.messages.node-id'),
          $this->trans('commands.create.nodes.messages.content-type'),
          $this->trans('commands.create.nodes.messages.title'),
          $this->trans('commands.create.nodes.messages.created'),
        ];

        $io->table($tableHeader, $nodes['success']);

        $io->success(
            sprintf(
                $this->trans('commands.create.nodes.messages.created-nodes'),
                $limit
            )
        );

        return;
    }
}
