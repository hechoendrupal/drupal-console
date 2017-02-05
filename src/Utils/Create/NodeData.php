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
    /* @var array */
    protected $bundles = [];

    /**
     * Nodes constructor.
     *
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param EntityFieldManagerInterface $entityFieldManager
     * @param DateFormatterInterface      $dateFormatter
     * @param array                       $bundles
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        DateFormatterInterface $dateFormatter,
        $bundles
    ) {
        $this->bundles = $bundles;
        parent::__construct(
            $entityTypeManager,
            $entityFieldManager,
            $dateFormatter
        );
    }

    /**
     * @param $contentTypes
     * @param $limit
     * @param $titleWords
     * @param $timeRange
     *
     * @return array
     */
    public function create(
        $contentTypes,
        $limit,
        $titleWords,
        $timeRange,
        $language = LanguageInterface::LANGCODE_NOT_SPECIFIED
    ) {
        $nodes = [];
        for ($i=0; $i<$limit; $i++) {
            $contentType = $contentTypes[array_rand($contentTypes)];
            $node = $this->entityTypeManager->getStorage('node')->create(
                [
                    'nid' => null,
                    'type' => $contentType,

                    'created' => REQUEST_TIME - mt_rand(0, $timeRange),
                    'uid' => $this->getUserID(),
                    'title' => $this->getRandom()->sentences(mt_rand(1, $titleWords), true),
                    'revision' => mt_rand(0, 1),
                    'status' => true,
                    'promote' => mt_rand(0, 1),
                    'langcode' => $language
                ]
            );

            $this->generateFieldSampleData($node);

            try {
                $node->save();
                $nodes['success'][] = [
                    'nid' => $node->id(),
                    'node_type' => $this->bundles[$contentType],
                    'title' => $node->getTitle(),
                    'created' => $this->dateFormatter->format(
                        $node->getCreatedTime(),
                        'custom',
                        'Y-m-d h:i:s'
                    )
                ];
            } catch (\Exception $error) {
                $nodes['error'][] = [
                    'node_type' =>  $this->bundles[$contentType],
                    'title' => $node->getTitle(),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $nodes;
    }
}
