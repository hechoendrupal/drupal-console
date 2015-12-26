<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\ContentNode.
 */

namespace Drupal\Console\Utils;

use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Class ContentNode
 * @package Drupal\Console\Utils
 */
class Content extends GenerateBase
{
    /**
   * @var array
   */
    protected $bundles = [];

    /**
     * ContentNode constructor.
     * @param EntityManagerInterface $entityManager
     * @param array                  $bundles
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        $bundles
    ) {
        $this->bundles = $bundles;
        parent::__construct($entityManager);
    }

    /**
     * @param $contentTypes
     * @param $limit
     * @param $titleWords
     * @param $timeRange
     *
     * @return array
     */
    public function createNode(
        $contentTypes,
        $limit,
        $titleWords,
        $timeRange
    ) {
        $nodes = [];
        for ($i=0; $i<$limit; $i++) {
            $contentType = $contentTypes[array_rand($contentTypes)];
            $node = $this->entityManager->getStorage('node')->create(
                [
                    'nid' => null,
                    'type' => $contentType,
                    'langcode' => 'en',
                    'created' => REQUEST_TIME - mt_rand(0, $timeRange),
                    'uid' => $this->getUserID(),
                    'title' => $this->getRandom()->sentences(mt_rand(1, $titleWords), true),
                    'revision' => mt_rand(0, 1),
                    'status' => true,
                    'promote' => mt_rand(0, 1),
                    'langcode' => 'en'
                ]
            );

            $this->generateFieldSampleData($node);

            try {
                $node->save();
                $nodes['success'][] = [
                    'node_type' => $this->bundles[$contentType],
                    'title' => $node->getTitle()
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
