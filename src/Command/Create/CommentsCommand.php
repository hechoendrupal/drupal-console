<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Create\CommentsCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Command\Shared\CreateTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class CommentsCommand
 * @package Drupal\Console\Command\Generate
 */
class CommentsCommand extends Command
{
    use CreateTrait;
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:comments')
            ->setDescription($this->trans('commands.create.comments.description'))
            ->addArgument(
                'node-id',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.create.comments.arguments.node-id'),
                null
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.comments.options.limit')
            )
            ->addOption(
                'title-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.comments.options.title-words')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.comments.options.time-range')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $nodeId  = $input->getArgument('node-id');
        if (!$nodeId) {
            $nodeId = $io->ask(
                $this->trans('commands.create.comments.questions.node-id')
            );
            $input->setArgument('node-id', $nodeId);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.comments.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $titleWords = $input->getOption('title-words');
        if (!$titleWords) {
            $titleWords = $io->ask(
                $this->trans('commands.create.comments.questions.title-words'),
                5
            );

            $input->setOption('title-words', $titleWords);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
                $this->trans('commands.create.comments.questions.time-range'),
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
        $createComments = $this->getApplication()->getDrupalApi()->getCreateComments();

        $nodeId = $input->getArgument('node-id')?:1;
        $limit = $input->getOption('limit')?:25;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:31536000;

        $comments = $createComments->createComment(
            $nodeId,
            $limit,
            $titleWords,
            $timeRange
        );

        $tableHeader = [
            $this->trans('commands.create.comments.messages.node-id'),
            $this->trans('commands.create.comments.messages.comment-id'),
            $this->trans('commands.create.comments.messages.title'),
            $this->trans('commands.create.comments.messages.created'),
        ];

        $io->table($tableHeader, $comments['success']);

        $io->success(
            sprintf(
                $this->trans('commands.create.comments.messages.created-comments'),
                $limit
            )
        );

        return;
    }
}
