<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\GenerateBase.
 */

namespace Drupal\Console\Utils;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Class ContentNode
 * @package Drupal\Console\Utils
 */
abstract class GenerateBase
{
    /**
 * @var EntityManagerInterface 
*/
    protected $entityManager = null;

    /**
 * @var array 
*/
    protected $users = [];

    /**
 * @var Random $random 
*/
    protected $random = null;

    /**
     * ContentNode constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $entity
     * @return array
     */
    private function getFields($entity)
    {
        $entityTypeId = $entity->getEntityType()->id();
        $bundle = $entity->bundle();

        $fields = array_filter(
            $this->entityManager->getFieldDefinitions($entityTypeId, $bundle), function ($fieldDefinition) {
                return $fieldDefinition instanceof FieldConfigInterface;
            }
        );

        return $fields;
    }

    /**
     * @param
     * @param $entity
     */
    protected function generateFieldSampleData($entity)
    {
        $fields = $this->getFields($entity);

        /* @var \Drupal\field\FieldConfigInterface[] $fields */
        foreach ($fields as $field) {
            $fieldName = $field->getFieldStorageDefinition()->getName();
            $cardinality = $field->getFieldStorageDefinition()->getCardinality(
            );
            if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
                $cardinality = rand(1, 5);
            }

            $entity->$fieldName->generateSampleItems($cardinality);
        }
    }

    /**
     * Returns the random data generator.
     *
     * @return \Drupal\Component\Utility\Random
     *   The random data generator.
     */
    protected function getRandom()
    {
        if (!$this->random) {
            $this->random = new Random();
        }

        return $this->random;
    }

    /**
     * Retrieve a random Uid of enabled users.
     *
     * @return array
     */
    protected function getUserId()
    {
        if (!$this->users) {
            $userStorage = $this->entityManager->getStorage('user');

            $this->users = $userStorage->loadByProperties(['status' => true]);
        }

        return array_rand($this->users);
    }
}
