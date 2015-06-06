<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\MigrateLoadHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class MigrateLoadHelper extends Helper
{
    /** @var $entity  */
    private $entity;

    /**
     * @param $entity_type  string
     * @param $yml          array
     */
    public function generateEntity($yml,$entity_type){
      $entity_manager =  \Drupal::entityManager();
      $entity_storage = $entity_manager->getStorage($entity_type);
      $this->entity = $entity_storage->createFromStorageRecord($yml); 

    }

    /**
     * @return entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'load';
    }

}
