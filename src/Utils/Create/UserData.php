<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Create\UserData.
 */

namespace Drupal\Console\Utils\Create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class Users
 * @package Drupal\Console\Utils\Create
 */
class UserData extends Base
{
    /* @var array */
    protected $roles = [];

    /**
     * Users constructor.
     *
     * @param EntityTypeManagerInterface  $entityTypeManager
     * @param EntityFieldManagerInterface $entityFieldManager
     * @param DateFormatterInterface      $dateFormatter
     * @param array                       $roles
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        DateFormatterInterface $dateFormatter,
        $roles
    ) {
        $this->roles = $roles;
        parent::__construct(
            $entityTypeManager,
            $entityFieldManager,
            $dateFormatter
        );
    }

    /**
     * Create and returns an array of new Users.
     *
     * @param $roles
     * @param $limit
     * @param $password
     * @param $timeRange
     *
     * @return array
     */
    public function create(
        $roles,
        $limit,
        $password,
        $timeRange
    ) {
        $users = [];
        for ($i=0; $i<$limit; $i++) {
            $username = $this->getRandom()->word(mt_rand(6, 12));

            $user = $this->entityTypeManager->getStorage('user')->create(
                [
                    'name' => $username,
                    'mail' => $username . '@example.com',
                    'pass' => $password?:$this->getRandom()->word(mt_rand(8, 16)),
                    'status' => mt_rand(0, 1),
                    'roles' => $roles[array_rand($roles)],
                    'created' => REQUEST_TIME - mt_rand(0, $timeRange),
                ]
            );

            try {
                $user->save();

                $userRoles = [];
                foreach ($user->getRoles() as $userRole) {
                    $userRoles[] = $this->roles[$userRole];
                }

                $users['success'][] = [
                    'user-id' => $user->id(),
                    'username' => $user->getUsername(),
                    'roles' => implode(', ', $userRoles),
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
