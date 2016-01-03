<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DeleteCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\CreateTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DeleteCommand
 * @package Drupal\Console\Command\User
 */
class DeleteCommand extends ContainerAwareCommand
{
    use CreateTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:delete')
            ->setDescription($this->trans('commands.user.delete.description'))
            ->addArgument(
                'user-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.user.delete.arguments.user-id')
            )
            ->addOption(
                'roles',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.user.debug.options.roles')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $userId = $input->getArgument('user-id');
        if (!$userId) {
            $userId = $io->askEmpty(
                $this->trans('commands.user.delete.questions.user-id'),
                null
            );
            $input->setArgument('user-id', $userId);
        }

        $roles = $input->getOption('roles');

        if (!$userId && !$roles) {
            $systemRoles = $this->getDrupalApi()->getRoles();
            unset($systemRoles['authenticated']);
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

        $user_id = $input->getArgument('user-id');

        if ($user_id && $user_id <= 1) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.delete.errors.invalid-user-id'),
                $user_id
                )
            );
            return;
        }

        if ($user_id) {
            $user = $this->getEntityManager()->getStorage('user')->load($user_id);

            if (!$user) {
                $text = $this->trans('commands.user.delete.errors.invalid-user');
                $text = SafeMarkup::format($text, ['@uid' => $user_id]);
                $io->error($text);
                return;
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
            }

            return;
        }

        $roles = $input->getOption('roles');

        if ($roles) {
            $entity_manager = $this->getEntityManager();
            $userStorage = $entity_manager->getStorage('user');

            $tableHeader = [
                $this->trans('commands.user.debug.messages.user-id'),
                $this->trans('commands.user.debug.messages.username'),
            ];

            $entity_query_service = $this->getEntityQuery();
            $query = $entity_query_service->get('user');
            $query->condition('roles', $roles, 'IN');
            $query->condition('uid', 1, '>');

            $results = $query->execute();

            $users = $userStorage->loadMultiple($results);
            $tableRows = [];
            foreach ($users as $user_id => $user) {
                $tableRows[] = [$user_id, $user->getUsername()];
                try {
                    $user->delete();
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                    return;
                }
            }

            $io->table($tableHeader, $tableRows, 'compact');

            $io->info(
                sprintf(
                    $this->trans('commands.user.delete.messages.users-deleted'),
                    count($tableRows)
                )
            );
        }
    }
}
