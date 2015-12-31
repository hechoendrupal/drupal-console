<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\Users.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Console\Utils\Create\Base;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\user\Entity\Role;

/**
 * Class Users
 * @package Drupal\Console\Utils
 */
class Users extends Base
{
    /**
     * Vocabularies constructor.
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
     * Create and returns an array of new Vocabularies.
     *
     * @param $roles
     * @param $limit
     * @param $password
     * @param $timeRange
     *
     * @return array
     */
    public function createUser(
        $roles,
        $limit,
        $password,
        $timeRange
    ) {
        $user = [];
        for ($i=0; $i<$limit; $i++) {

            // Create a vocabulary.
            $username = $this->getRandom()->word(mt_rand(6, 12));

            if (!$password) {
                $pass = $this->getRandom()->word(mt_rand(8, 16));
            } else {
                $pass = $password;
            }
            $user = $this->entityManager->getStorage('user')->create(
                [
                    'name' => $username,
                    'mail' => $username . '@example.com',
                    'pass' => $pass,
                    'status' => mt_rand(0, 1),
                    'roles' => $roles[array_rand($roles)],
                    'created' => REQUEST_TIME - mt_rand(0, $timeRange),
                ]
            );

            try {
                $user->save();

                $username = $user->get('name')->getValue();

                $rids = $user->getRoles();
                $roles = [];
                foreach ($rids as $rid) {
                    $role = Role::load($rid);
                    if ($role) {
                        $roles[$rid] = $role->get('label');
                    }
                }

                $users['success'][] = [
                    'user-id' => $user->id(),
                    'username' => $username[0]['value'],
                    'roles' => implode(',', $roles),
                    'created' => $this->dateFormatter->format(
                        $user->getCreatedTime(),
                        'custom',
                        'Y-m-d h:i:s'
                    )

                ];
            } catch (\Exception $error) {
                $users['error'][] = [
                    'vid' => $user->id(),
                    'name' => $user->get('name'),
                    'error' => $error->getMessage()
                ];
            }
        }

        return $users;
    }
}
