<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\CommentData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class Nodes
 *
 * @package Drupal\Console\Utils\Create
 */
class CommentData extends Base
{
    /**
     * @param $nid
     * @param $limit
     * @param $titleWords
     * @param $timeRange
     *
     * @return array
     */
    public function create(
        $nid,
        $limit,
        $titleWords,
        $timeRange
    ) {
        $comments = [];

        for ($i = 0; $i < $limit; $i++) {
            try {
                $comment = $this->entityTypeManager->getStorage('comment')->create(
                    [
                        'entity_id' => $nid,
                        'entity_type' => 'node',
                        'field_name' => 'comment',
                        'created' => \Drupal::time()->getRequestTime() - mt_rand(0, $timeRange),
                        'uid' => $this->getUserID(),
                        'status' => true,
                        'subject' => $this->getRandom()->sentences(mt_rand(1, $titleWords), true),
                        'language' => 'und',
                        'comment_body' => ['und' => ['random body']],
                    ]
                );

                $this->generateFieldSampleData($comment);

                $comment->save();
                $comments['success'][] = [
                    'nid' => $nid,
                    'cid' => $comment->id(),
                    'title' => $comment->getSubject(),
                    'created' => $this->dateFormatter->format(
                        $comment->getCreatedTime(),
                        'custom',
                        'Y-m-d h:i:s'
                    ),
                ];
            } catch (\Exception $error) {
                $comments['error'][] = $error->getMessage();
            }
        }

        return $comments;
    }
}
