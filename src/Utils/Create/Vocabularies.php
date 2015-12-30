<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\Vocabularies.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Console\Utils\Create\Base;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class Terms
 * @package Drupal\Console\Utils
 */
class Vocabularies extends Base
{
    /**
     * Terms constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DateFormatterInterface $dateFormatter
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        DateFormatterInterface $dateFormatter
    ) {
        parent::__construct($entityManager, $dateFormatter);
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
    public function createVocabulary(
        $limit,
        $nameWords
    ) {
        $vocabularies = [];
        for ($i=0; $i<$limit; $i++) {

            // Create a vocabulary.
            $vocabulary = entity_create(
                'taxonomy_vocabulary', array(
                'name' => $this->getRandom()->sentences(mt_rand(1, $nameWords), true),
                'description' => $this->getRandom()->sentences(),
                'vid' => Unicode::strtolower($this->getRandom()->name()),
                'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
                'weight' => mt_rand(0, 10),
                'id' => null,
                )
            );


            try {
                $vocabulary->save();
                ;
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
