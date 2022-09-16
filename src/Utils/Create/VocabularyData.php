<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\VocabularyData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class Vocabularies
 *
 * @package Drupal\Console\Utils
 */
class VocabularyData extends Base
{
    /**
     * Create and returns an array of new Vocabularies.
     *
     * @param $limit
     * @param $nameWords
     *
     * @return array
     */
    public function create(
        $limit,
        $nameWords
    ) {
        $vocabularies = [];
        for ($i = 0; $i < $limit; $i++) {
            try {
                // Create a vocabulary.
                $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->create(
                    [
                        'name' => $this->getRandom()->sentences(mt_rand(1, $nameWords), true),
                        'description' => $this->getRandom()->sentences(mt_rand(1, $nameWords)),
                        'vid' => mb_strtolower($this->getRandom()->name()),
                        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
                        'weight' => mt_rand(0, 10),
                    ]
                );
                $vocabulary->save();
                $vocabularies['success'][] = [
                    'vid' => $vocabulary->id(),
                    'vocabulary' => $vocabulary->get('name'),
                ];
            } catch (\Exception $error) {
                $vocabularies['error'][] = $error->getMessage();
            }
        }

        return $vocabularies;
    }
}
