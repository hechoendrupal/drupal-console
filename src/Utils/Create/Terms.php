<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\Terms.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Console\Utils\Create\Base;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class Terms
 * @package Drupal\Console\Utils
 */
class Terms extends Base
{
    /* @var array */
    protected $vocabularies = [];

    /**
     * ContentNode constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DateFormatterInterface $dateFormatter
     * @param array                  $vocabularies
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DateFormatterInterface $dateFormatter,
        $vocabularies
    ) {
        $this->vocabularies = $vocabularies;
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
    public function createTerm(
        $vocabularies,
        $limit,
        $nameWords
    ) {
        $terms = [];
        $values = [];
        for ($i=0; $i<$limit; $i++) {
            $vocabulary = Vocabulary::load($vocabularies[array_rand($vocabularies)]);
            $filter_formats = filter_formats();
            $format = array_pop($filter_formats);
            $term = entity_create(
                'taxonomy_term', $values + array(
                    'name' => $this->getRandom()->sentences(mt_rand(1, $nameWords), true),
                    'description' => array(
                        'value' => $this->getRandom()->sentences(),
                        // Use the first available text format.
                        'format' => $format->id(),
                    ),
                    'vid' => $vocabulary->id(),
                    'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
                )
            );


            try {
                $term->save();
                $terms['success'][] = [
                    'tid' => $term->id(),
                    'vocabulary' => $vocabulary->get('name'),
                    'name' => $term->getName(),
                ];
            } catch (\Exception $error) {
                $terms['error'][] = [
                    'vocabulary' => $vocabularies[$vocabulary],
                    'name' => $term->getName(),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $terms;
    }
}
