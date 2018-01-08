<?php

namespace Drupal\Console\Command\User;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\Query\QueryFactory;

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
		 * @var QueryFactory
		 */
		protected $entityQuery;

    /**
     * Base constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
				QueryFactory $entityQuery
    ) {
        $this->entityTypeManager = $entityTypeManager;
			  $this->entityQuery = $entityQuery;
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

		/**
		 * @param $user mixed
		 *
		 * @return mixed
		 */
		public function getUserName()
		{
			//$query = $this->entityQuery->get('user');
			$query =  \Drupal::entityQuery('user');
			$query->sort('uid');

			$results = $query->execute();

			$userStorage = \Drupal::entityManager()->getStorage('user');
					//$this->entityTypeManager->getStorage('user');
			$users = $userStorage->loadMultiple($results);

			$users = [];
			foreach ($users as $userId => $user) {
				 $users[$userId] = $user->getUsername();
			}

			return $users;

		}
}
