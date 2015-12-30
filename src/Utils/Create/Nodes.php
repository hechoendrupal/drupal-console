<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\Nodes.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Console\Utils\Create\Base;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class Nodes
 * @package Drupal\Console\Utils
 */
class Nodes extends Base
{
    /* @var array */
    protected $bundles = [];

    /**
     * Nodes constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DateFormatterInterface $dateFormatter
     * @param array                  $bundles
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DateFormatterInterface $dateFormatter,
        $bundles
    ) {
        $this->bundles = $bundles;
        parent::__construct($entityManager, $dateFormatter);
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
                    'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED
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
