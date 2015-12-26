<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\ContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ContentCommand
 * @package Drupal\Console\Command\Generate
 */
class ContentCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:content')
            ->setDescription($this->trans('commands.generate.content.description'))
            ->addArgument(
                'content-types',
                InputArgument::IS_ARRAY,
                $this->trans('commands.generate.content.arguments.content-types')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.content.arguments.limit')
            )
            ->addOption(
                'title-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.content.arguments.title-words')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.content.arguments.time-range')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --content type argument
        $contentTypes = $input->getArgument('content-types');
        if (!$contentTypes) {
            $bundles = $this->getDrupalApi()->getBundles();
            $contentTypes = $io->choice(
                $this->trans('commands.generate.content.questions.content-type'),
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
                $this->trans('commands.generate.content.questions.limit'),
                10
            );
            $input->setOption('limit', $limit);
        }

        $titleWordsMin = $input->getOption('title-words');
        if (!$titleWordsMin) {
            $titleWordsMin = $io->ask(
                $this->trans('commands.generate.content.questions.title-words'),
                5
            );

            $input->setOption('title-words', $titleWordsMin);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
                $this->trans('commands.generate.content.questions.time-range'),
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

        $contentGenerator = $this->getDrupalApi()->getContentGenerator();

        $contentTypes = $input->getArgument('content-types');
        $limit = $input->getOption('limit')?:10;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:'N';

        $nodes = $contentGenerator->createNode(
            $contentTypes,
            $limit,
            $titleWords,
            $timeRange
        );

        $tableHeader = [
          $this->trans('commands.generate.content.messages.content-type'),
          $this->trans('commands.generate.content.messages.title'),
        ];

        $io->success($this->trans('commands.generate.content.messages.generated-content'));
        $io->table($tableHeader, $nodes['success']);

        return;
    }

    /**
     * @return array
     */
    private function getTimeRange()
    {
        $timeRanges = [
            1 => sprintf('N | %s', $this->trans('commands.generate.content.questions.time-ranges.0')),
            3600 => sprintf('H | %s', $this->trans('commands.generate.content.questions.time-ranges.1')),
            86400 => sprintf('D | %s', $this->trans('commands.generate.content.questions.time-ranges.2')),
            604800 => sprintf('W | %s', $this->trans('commands.generate.content.questions.time-ranges.3')),
            2592000 => sprintf('M | %s', $this->trans('commands.generate.content.questions.time-ranges.4')),
            31536000 => sprintf('Y | %s', $this->trans('commands.generate.content.questions.time-ranges.5'))
        ];

        return $timeRanges;
    }
}
