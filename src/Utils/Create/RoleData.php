<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\RoleData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class Roles
 *
 * @package Drupal\Console\Utils\Create
 */
class RoleData extends Base
{
    /**
     * Roles constructor.
     *
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param EntityFieldManagerInterface $entityFieldManager
     * @param DateFormatterInterface      $dateFormatter
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        DateFormatterInterface $dateFormatter
    ) {
        parent::__construct(
            $entityTypeManager,
            $entityFieldManager,
            $dateFormatter
        );
    }

    /**
     * Create and returns an array of new Roles.
     *
     * @param $roles
     * @param $limit
     * @param $password
     * @param $timeRange
     *
     * @return array
     */
    public function create(
        $limit
    ) {
        $roles = [];
        for ($i=0; $i<$limit; $i++) {
            $rolename = $this->getRandom()->word(mt_rand(6, 12));

            $role = $this->entityTypeManager->getStorage('user_role')->create(
                [
                    'id' => $rolename,
                    'label' => $rolename,
                    'originalId' => $rolename
                ]
            );

            try {
                $role->save();

                $roles['success'][] = [
                    'role-id' => $role->id(),
                    'role-name' => $role->get('label')
                ];
            } catch (\Exception $error) {
                $roles['error'][] = [
                    'vid' => $role->id(),
                    'name' => $role->get('label'),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $roles;
    }
}
