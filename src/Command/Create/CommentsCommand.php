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
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\CreateTrait;
use Drupal\Console\Style\DrupalStyle;


/**
 * Class CommentsCommand
 * @package Drupal\Console\Command\Generate
 */
class CommentsCommand extends ContainerAwareCommand
{
    use CreateTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:comments')
            ->setDescription($this->trans('commands.create.comments.description'))
            ->addArgument(
                'entity-id',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.create.comments.arguments.entity-id'),
                null
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.comments.arguments.limit')
            )
            ->addOption(
                'title-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.comments.arguments.title-words')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.comments.arguments.time-range')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $entityId  = $input->getArgument('entity-id');
        if (!$entityId) {
            $entityId = $io->ask(
                $this->trans('Entity ID where the comments will be created')
            );
            $input->setArgument('entity-id', $entityId);
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
        $createComments = $this->getDrupalApi()->getCreateComments();

        $nodeId = $input->getArgument('entity-id');
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
