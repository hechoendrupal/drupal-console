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
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\CreateTrait;
use Drupal\Console\Utils\Create\NodeData;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class NodesCommand
 * @package Drupal\Console\Command\Generate
 */
class NodesCommand extends Command
{
    use CreateTrait;
    use CommandTrait;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;
    /**
     * @var NodeData
     */
    protected $createNodeData;

    /**
     * NodesCommand constructor.
     * @param DrupalApi $drupalApi
     * @param NodeData  $createNodeData
     */
    public function __construct(
        DrupalApi $drupalApi,
        NodeData $createNodeData
    ) {
        $this->drupalApi = $drupalApi;
        $this->createNodeData = $createNodeData;
        parent::__construct();
    }

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
            $bundles = $this->drupalApi->getBundles();
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

        $contentTypes = $input->getArgument('content-types');
        $limit = $input->getOption('limit')?:25;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:31536000;
        $available_types = array_keys($this->drupalApi->getBundles());

        foreach ($contentTypes as $type) {
            if (!in_array($type, $available_types)) {
                throw new \Exception('Invalid content type name given.');
            }
        }

        if (!$contentTypes) {
            $contentTypes = $available_types;
        }

        $nodes = $this->createNodeData->create(
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

        return 0;
    }
}
