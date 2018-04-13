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
 *
 * @package Drupal\Console\Utils
 */
class TermData extends Base
{
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
        $siteVocabularies = $this->drupalApi->getVocabularies();
        $terms = [];
        for ($i = 0; $i < $limit; $i++) {
            try {
                $vocabulary = $vocabularies[array_rand($vocabularies)];
                $term = $this->entityTypeManager->getStorage('taxonomy_term')->create(
                    [
                        'vid' => $vocabulary,
                        'name' => $this->getRandom()->sentences(mt_rand(1, $nameWords), true),
                        'description' => [
                            'value' => $this->getRandom()->sentences(mt_rand(1, $nameWords)),
                            'format' => 'full_html',
                        ],
                        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
                    ]
                );
                $term->save();
                $terms['success'][] = [
                    'tid' => $term->id(),
                    'vocabulary' => $siteVocabularies[$vocabulary],
                    'name' => $term->getName(),
                ];
            } catch (\Exception $error) {
                $terms['error'][] = $error->getMessage();
            }
        }

        return $terms;
    }
}
