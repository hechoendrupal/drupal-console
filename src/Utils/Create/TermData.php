<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\TermData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class Terms
 * @package Drupal\Console\Utils
 */
class TermData extends Base
{
    /* @var array */
    protected $vocabularies = [];

    /**
     * Terms constructor.
     *
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param EntityFieldManagerInterface $entityFieldManager
     * @param DateFormatterInterface      $dateFormatter
     * @param array                       $vocabularies
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        DateFormatterInterface $dateFormatter,
        $vocabularies
    ) {
        $this->vocabularies = $vocabularies;
        parent::__construct(
            $entityTypeManager,
            $entityFieldManager,
            $dateFormatter
        );
    }

    /**
     * Create and returns an array of new Terms.
     *
     * @param $vocabularies
     * @param $limit
     * @param $nameWords
     *
     * @return array
     */
    public function create(
        $vocabularies,
        $limit,
        $nameWords
    ) {
        $terms = [];
        for ($i=0; $i<$limit; $i++) {
            $vocabulary = $vocabularies[array_rand($vocabularies)];
            $term = $this->entityTypeManager->getStorage('taxonomy_term')->create(
                [
                    'vid' => $vocabulary,
                    'name' => $this->getRandom()->sentences(mt_rand(1, $nameWords), true),
                    'description' => array(
                        'value' => $this->getRandom()->sentences(),
                        'format' => 'full_html',
                    ),
                    'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
                ]
            );

            try {
                $term->save();
                $terms['success'][] = [
                    'tid' => $term->id(),
                    'vocabulary' => $this->vocabularies[$vocabulary],
                    'name' => $term->getName(),
                ];
            } catch (\Exception $error) {
                $terms['error'][] = [
                    'vocabulary' => $this->vocabularies[$vocabulary],
                    'name' => $term->getName(),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $terms;
    }
}
