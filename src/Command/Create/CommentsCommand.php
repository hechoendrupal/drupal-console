<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Create\CommentsCommand.
 */

namespace Drupal\Console\Command\Create;

use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Command\Shared\CreateTrait;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Create\CommentData;
use Drupal\node\Entity\Node;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommentsCommand
 *
 * @package Drupal\Console\Command\Generate
 *
 * @DrupalCommand(
 *     extension = "comment",
 *     extensionType = "module"
 * )
 */
class CommentsCommand extends Command
{
    use CreateTrait;

    /**
     * @var CommentData
     */
    protected $createCommentData;

    /**
     * CommentsCommand constructor.
     *
     * @param CommentData $createCommentData
     */
    public function __construct(CommentData $createCommentData)
    {
        $this->createCommentData = $createCommentData;
        parent::__construct();
    }

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
            )->setAliases(['crc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $nodeId  = $input->getArgument('node-id');
        if (!$nodeId) {
            $nodeId = $this->getIo()->ask(
                $this->trans('commands.create.comments.questions.node-id')
            );
            $input->setArgument('node-id', $nodeId);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $this->getIo()->ask(
                $this->trans('commands.create.comments.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }

        $titleWords = $input->getOption('title-words');
        if (!$titleWords) {
            $titleWords = $this->getIo()->ask(
                $this->trans('commands.create.comments.questions.title-words'),
                5
            );

            $input->setOption('title-words', $titleWords);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $this->getIo()->choice(
                $this->trans('commands.create.comments.questions.time-range'),
                array_values($timeRanges)
            );

            $input->setOption('time-range', array_search($timeRange, $timeRanges));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nodeId = $input->getArgument('node-id')?:1;
        $node = \Drupal\node\Entity\Node::load($nodeId);
        if (empty($node)) {
            throw new \InvalidArgumentException(
                $this->trans(
                    'commands.generate.controller.messages.node-id-invalid'
                )
            );
        }
        $limit = $input->getOption('limit')?:25;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:31536000;

        $result = $this->createCommentData->create(
            $nodeId,
            $limit,
            $titleWords,
            $timeRange
        );

        if ($result['success']) {

            $tableHeader = [
                $this->trans('commands.create.comments.messages.node-id'),
                $this->trans('commands.create.comments.messages.comment-id'),
                $this->trans('commands.create.comments.messages.title'),
                $this->trans('commands.create.comments.messages.created'),
            ];

            $this->getIo()->table($tableHeader, $result['success']);

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.create.comments.messages.created-comments'),
                    count($result['success'])
                )
            );
        }

        if (isset($result['error'])) {
            foreach ($result['error'] as $error) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.create.comments.messages.error'),
                        $error
                    )
                );
            }
        }

        return 0;
    }
}
