<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DebugCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument(
                'roles',
                InputArgument::IS_ARRAY,
                $this->trans('commands.user.debug.arguments.roles')
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
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $roles = $input->getArgument('roles');
        if (!$roles) {
            $systemRoles = $this->getDrupalApi()->getRoles();
            $roles = $io->choice(
                $this->trans('commands.user.debug.questions.roles'),
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

            $input->setArgument('roles', $roles);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.user.debug.questions.limit'),
                25
            );
            $input->setOption('limit', $limit);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity_manager = $this->getEntityManager();
        $userStorage = $entity_manager->getStorage('user');

        $io = new DrupalStyle($input, $output);

        $systemRoles = $this->getDrupalApi()->getRoles();

        $roles = $input->getArgument('roles');
        $limit = $input->getOption('limit')?:25;

        $entity_query_service = $this->getEntityQuery();
        $query = $entity_query_service->get('user');
        $query->condition('roles', $roles, 'IN');
        $query->range(0, $limit);

        $results = $query->execute();

        $users = $userStorage->loadMultiple($results);

        $tableHeader = [
            $this->trans('commands.user.debug.messages.user-id'),
            $this->trans('commands.user.debug.messages.username'),
            $this->trans('commands.user.debug.messages.roles'),
            $this->trans('commands.user.debug.messages.status'),
        ];

        $tableRows = [];
        foreach ($users as $user_id => $user) {
            $userRoles = [];
            foreach ($user->getRoles() as $userRole) {
                $userRoles[] = $systemRoles[$userRole];
            }

            $status = $user->isActive()?$this->trans('commands.common.status.enabled'):$this->trans('commands.common.status.disabled');
            $tableRows[] = [$user_id, $user->getUsername(), implode(', ', $userRoles), $status];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }
}
