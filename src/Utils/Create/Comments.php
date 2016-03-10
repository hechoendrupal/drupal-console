<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\Nodes.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\Node;
use Drupal\comment\Entity\Comment;

/**
 * Class Nodes
 * @package Drupal\Console\Utils
 */
class Comments extends Base
{
    /* @var array */
    protected $bundles = [];

    /**
     * Comments constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DateFormatterInterface $dateFormatter
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DateFormatterInterface $dateFormatter
    ) {
        parent::__construct($entityManager, $dateFormatter);
    }

    /**
     * @param $nid,
     * @param $limit
     * @param $titleWords
     * @param $timeRange
     *
     * @return array
     */
    public function createComment(
        $nid,
        $limit,
        $titleWords,
        $timeRange
    ) {
        $comments = [];
        $node = Node::load($nid);

        for ($i=0; $i<$limit; $i++) {
            $comment = Comment::create([
                'entity_id' => $node->id(),
                'entity_type' => 'node',
                'field_name' => 'comment',
                'created' => REQUEST_TIME - mt_rand(0, $timeRange),
                'uid' => $this->getUserID(),
                'status' => true,
                'subject' => $this->getRandom()->sentences(mt_rand(1, $titleWords), true),
                'language' => 'und',
                'comment_body' => ['und' => ['random body']],
            ]);

            $this->generateFieldSampleData($comment);

            try {
                $comment->save();
                $comments['success'][] = [
                    'cid' => $comment->id(),
                    'title' => $comment->getSubject(),
                    'created' => $this->dateFormatter->format(
                        $comment->getCreatedTime(),
                        'custom',
                        'Y-m-d h:i:s'
                    )
                ];
            } catch (\Exception $error) {
                $comments['error'][] = [
                    'title' => $comment->getTitle(),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $comments;
    }
}
