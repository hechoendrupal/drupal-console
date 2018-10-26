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
            $userEntities = $this->entityTypeManager
                ->getStorage('user')
                ->loadByProperties(['name' => $user]);
            $userEntity = reset($userEntities);
        }

        return $userEntity;
    }

    /***
     * @return array users from site
     */
    public function getUsers()
    {
        $userStorage =  $this->entityTypeManager->getStorage('user');
        $users = $userStorage->loadMultiple();

        $userList = [];
        foreach ($users as $userId => $user) {
            $userList[$userId] = $user->getUsername();
        }

        return $userList;
    }

    private function userQuestion($user)
    {
        if (!$user) {
            $user = $this->getIo()->choiceNoList(
                $this->trans('commands.user.password.reset.questions.user'),
                $this->getUsers()
            );
        }

        return $user;
    }

    public function getUserOption()
    {
        $input = $this->getIo()->getInput();

        $user = $this->userQuestion($input->getOption('user'));
        $input->setOption('user', $user);

        return $user;
    }

    public function getUserArgument()
    {
        $input = $this->getIo()->getInput();

        $user = $this->userQuestion($input->getArgument('user'));
        $input->setArgument('user', $user);

        return $user;
    }
}
