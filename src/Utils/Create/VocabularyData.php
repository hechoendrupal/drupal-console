<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\VocabularyData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Component\Utility\Unicode;
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
     * Vocabularies constructor.
     *
     * @param EntityTypeManagerInterface  $entityManager
     * @param EntityFieldManagerInterface $entityFieldManager
     * @param DateFormatterInterface      $dateFormatter
     */
    public function __construct(
        EntityTypeManagerInterface $entityManager,
        EntityFieldManagerInterface $entityFieldManager,
        DateFormatterInterface $dateFormatter
    ) {
        parent::__construct(
            $entityManager,
            $entityFieldManager,
            $dateFormatter
        );
    }

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
        for ($i=0; $i<$limit; $i++) {

            // Create a vocabulary.
            $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->create(
                [
                    'name' => $this->getRandom()->sentences(mt_rand(1, $nameWords), true),
                    'description' => $this->getRandom()->sentences(),
                    'vid' => Unicode::strtolower($this->getRandom()->name()),
                    'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
                    'weight' => mt_rand(0, 10),
                ]
            );

            try {
                $vocabulary->save();
                $vocabularies['success'][] = [
                    'vid' => $vocabulary->id(),
                    'vocabulary' => $vocabulary->get('name'),
                ];
            } catch (\Exception $error) {
                $vocabularies['error'][] = [
                    'vid' => $vocabulary->id(),
                    'name' => $vocabulary->get('name'),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $vocabularies;
    }
}
