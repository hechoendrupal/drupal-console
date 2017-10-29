<?php

namespace Drupal\Console\Command\User;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Command\Command;

/**
 * Class UserBase
 *
 * @package Drupal\Console\Command\User
 */
class UserBase extends Command
{
    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Base constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        parent::__construct();
    }

    /**
     * @param $user mixed
     *
     * @return mixed
     */
    public function getUserEntity($user)
    {
        if (is_numeric($user)) {
            $userEntity = $this->entityTypeManager
                ->getStorage('user')
                ->load($user);
        } else {
            $userEntity = reset(
                $this->entityTypeManager
                    ->getStorage('user')
                    ->loadByProperties(['name' => $user])
            );
        }

        return $userEntity;
    }
}
