<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\NodeData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class Nodes
 *
 * @package Drupal\Console\Utils
 */
class NodeData extends Base
{
    /**
     * @param $contentTypes
     * @param $limit
     * @param $titleWords
     * @param $timeRange
     * @param $revision
     *
     * @return array
     */
    public function create(
        $contentTypes,
        $limit,
        $titleWords,
        $timeRange,
        $revision,
        $language = LanguageInterface::LANGCODE_NOT_SPECIFIED
    ) {
        $nodes = [];
        $bundles = $this->drupalApi->getBundles();
        for ($i = 0; $i < $limit; $i++) {
            try {
                $contentType = $contentTypes[array_rand($contentTypes)];
                $node = $this->entityTypeManager->getStorage('node')->create(
                    [
                        'nid' => null,
                        'type' => $contentType,
                        'created' => \Drupal::time()->getRequestTime() - mt_rand(0, $timeRange),
                        'uid' => $this->getUserID(),
                        'title' => $this->getRandom()->sentences(mt_rand(1, $titleWords), true),
                        'revision' => mt_rand(0, 1),
                        'status' => true,
                        'promote' => mt_rand(0, 1),
                        'langcode' => $language
                    ]
                );

                $this->generateFieldSampleData($node);
                $node->save();

                if($revision) {
                    for ($a = 0; $a < 3; $a++) {
                        $this->addRevision($node, $a);
                    }
                }

                $nodes['success'][] = [
                    'nid' => $node->id(),
                    'node_type' => $bundles[$contentType],
                    'title' => $node->getTitle(),
                    'created' => $this->dateFormatter->format(
                        $node->getCreatedTime(),
                        'custom',
                        'Y-m-d h:i:s'
                    )
                ];
            } catch (\Exception $error) {
                $nodes['error'][] = $error->getMessage();
            }
        }

        return $nodes;
    }

    /**
     * @param $node
     * @param $count
     */
    private function addRevision($node, $count) {
        $node->setTitle($this->getRandom()->sentences(mt_rand(1, 5), true));
        $node->setNewRevision(TRUE);
        $node->revision_log = "Revision number $count was created";
        $node->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $node->save();
    }
}
