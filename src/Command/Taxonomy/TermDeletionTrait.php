<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 28/07/16
 * Time: 10:53
 */

namespace Drupal\Console\Command\Taxonomy;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;


trait TermDeletionTrait {


  /**
   * Destroy all existing terms 
   * @param $vid
   * @throws \Exception
   */
  private function deleteExistingTerms($vid,$io)
  {
    //Load the vid
    $vocabularies = Vocabulary::loadMultiple();
    if (!isset($vocabularies[$vid])) {
      throw new \Exception("vid {$vid} does not exist");
    }

    $selected_vocab = $vocabularies[$vid];
    $terms = \Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('taxonomy_term')
      ->loadTree($selected_vocab->id());

    foreach ($terms as $term) {
      $treal = Term::load($term->tid);
      if($treal !== null){
        $io->info("Deleting {$term->name} and all translations");
        $treal->delete();
      }
    }
  }

}