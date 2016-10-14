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
   * Destroy all existing terms before import
   * @param $vid
   * @throws \Exception
   */
  private function deleteExistingTerms($vid = null,$io)
  {
    //Load the vid
    $vocabularies = Vocabulary::loadMultiple();

    if($vid !== 'all'){
      $vid = [$vid];
    } else {
      $vid = array_keys($vocabularies);
    }

    foreach ($vid as $item) {
      if (!isset($vocabularies[$item])) {
        throw new \Exception("vid {$item} does not exist");
      }
      $selected_vocab = $vocabularies[$item];
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

}