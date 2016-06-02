<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DeleteCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DeleteCommand
 * @package Drupal\Console\Command\User
 */
class DeleteCommand extends Command
{
    use ContainerAwareCommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:delete')
            ->setDescription($this->trans('commands.user.delete.description'))
            ->addOption(
                'user-id',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.delete.options.user-id')
            )
            ->addOption(
                'roles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.delete.options.roles')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $userId = $input->getOption('user-id');
        if (!$userId) {
            $userId = $io->askEmpty(
                $this->trans('commands.user.delete.questions.user-id'),
                null
            );
            $input->setOption('user-id', $userId);
        }

        $roles = $input->getOption('roles');

        if (!$userId && !$roles) {
            $systemRoles = $this->getApplication()->getDrupalApi()->getRoles(false, false, false);
            $roles = $io->choice(
                $this->trans('commands.user.delete.questions.roles'),
                array_values($systemRoles),
                null,
                true
            );

            $roles = array_map(
                function ($role) use ($systemRoles) {
                    return array_search($role, $systemRoles);
                },
                $roles
            );

            $input->setOption('roles', $roles);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $userId = $input->getOption('user-id');

        if ($userId && $userId <= 1) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.delete.errors.invalid-user-id'),
                    $userId
                )
            );

            return 1;
        }

        if ($userId) {
            $user = $this->getDrupalService('entity_type.manager')
                ->getStorage('user')
                ->load($userId);

            if (!$user) {
                $io->error(
                    sprintf(
                        $this->trans('commands.user.delete.errors.invalid-user'),
                        $userId
                    )
                );

                return 1;
            }

            try {
                $user->delete();
                $io->info(
                    sprintf(
                        $this->trans('commands.user.delete.messages.user-deleted'),
                        $user->getUsername()
                    )
                );
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return 1;
            }
        }

        $roles = $input->getOption('roles');

        if ($roles) {
            $entityManager = $this->getDrupalService('entity_type.manager');
            $userStorage = $entityManager->getStorage('user');
            $entityQuery = $this->getDrupalService('entity.query');

            $query = $entityQuery->get('user');
            $query->condition('roles', is_array($roles)?$roles:[$roles], 'IN');
            $query->condition('uid', 1, '>');
            $results = $query->execute();

            $users = $userStorage->loadMultiple($results);

            $tableHeader = [
              $this->trans('commands.user.debug.messages.user-id'),
              $this->trans('commands.user.debug.messages.username'),
            ];

            $tableRows = [];
            foreach ($users as $userId => $user) {
                try {
                    $user->delete();
                    $tableRows['success'][] = [$userId, $user->getUsername()];
                } catch (\Exception $e) {
                    $tableRows['error'][] = [$userId, $user->getUsername()];
                    $io->error($e->getMessage());

                    return 1;
                }
            }

            if ($tableRows['success']) {
                $io->table($tableHeader, $tableRows['success']);
                $io->success(
                    sprintf(
                        $this->trans('commands.user.delete.messages.users-deleted'),
                        count($tableRows['success'])
                    )
                );
            }
        }
    }
}
