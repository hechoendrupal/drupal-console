<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DebugCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\User
 */
class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:debug')
            ->setDescription($this->trans('commands.user.debug.description'))
            ->addOption(
                'roles',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.debug.options.roles')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.debug.options.limit')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $roles = $input->getOption('roles');
        $limit = $input->getOption('limit');

        $entityManager = $this->getEntityManager();
        $userStorage = $entityManager->getStorage('user');
        $systemRoles = $this->getDrupalApi()->getRoles();

        $entityQuery = $this->getEntityQuery();
        $query = $entityQuery->get('user');
        $query->condition('uid', 0, '>');
        $query->sort('uid');

        if ($roles) {
            $query->condition('roles', is_array($roles)?$roles:[$roles], 'IN');
        }

        if ($limit) {
            $query->range(0, $limit);
        }

        $results = $query->execute();
        $users = $userStorage->loadMultiple($results);

        $tableHeader = [
            $this->trans('commands.user.debug.messages.user-id'),
            $this->trans('commands.user.debug.messages.username'),
            $this->trans('commands.user.debug.messages.roles'),
            $this->trans('commands.user.debug.messages.status'),
        ];

        $tableRows = [];
        foreach ($users as $userId => $user) {
            $userRoles = [];
            foreach ($user->getRoles() as $userRole) {
                $userRoles[] = $systemRoles[$userRole];
            }

            $status = $user->isActive()?$this->trans('commands.common.status.enabled'):$this->trans('commands.common.status.disabled');
            $tableRows[] = [$userId, $user->getUsername(), implode(', ', $userRoles), $status];
        }

        $io->table($tableHeader, $tableRows);
    }
}
