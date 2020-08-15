<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DebugCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class UserCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class UserCommand extends Command
{
    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * DebugCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param DrupalApi                  $drupalApi
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        DrupalApi $drupalApi
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:user')
            ->setDescription($this->trans('commands.debug.user.description'))
            ->addOption(
                'uid',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.debug.user.options.uid')
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.debug.user.options.username')
            )
            ->addOption(
                'mail',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.debug.user.options.mail')
            )
            ->addOption(
                'roles',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.user.options.roles')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.user.options.limit')
            )->setAliases(['dus']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $roles = $input->getOption('roles');
        $limit = $input->getOption('limit');

        $uids = $this->splitOption($input->getOption('uid'));
        $usernames = $this->splitOption($input->getOption('username'));
        $mails = $this->splitOption($input->getOption('mail'));

        $userStorage = $this->entityTypeManager->getStorage('user');
        $systemRoles = $this->drupalApi->getRoles();

        $query = $this->entityTypeManager->getStorage('user')->getQuery();
        $query->condition('uid', 0, '>');
        $query->sort('uid');


        // uid as option
        if (is_array($uids) && $uids) {
            $group = $query->andConditionGroup()
                ->condition('uid', $uids, 'IN');
            $query->condition($group);
        }

        // username as option
        if (is_array($usernames) && $usernames) {
            $group = $query->andConditionGroup()
                ->condition('name', $usernames, 'IN');
            $query->condition($group);
        }

        // mail as option
        if (is_array($mails) && $mails) {
            $group = $query->andConditionGroup()
                ->condition('mail', $mails, 'IN');
            $query->condition($group);
        }

        if ($roles) {
            $query->condition('roles', is_array($roles)?$roles:[$roles], 'IN');
        }

        if ($limit) {
            $query->range(0, $limit);
        }

        $results = $query->execute();
        $users = $userStorage->loadMultiple($results);

        $tableHeader = [
            $this->trans('commands.debug.user.messages.user-id'),
            $this->trans('commands.debug.user.messages.username'),
            $this->trans('commands.debug.user.messages.roles'),
            $this->trans('commands.debug.user.messages.status'),
        ];

        $tableRows = [];
        foreach ($users as $userId => $user) {
            $userRoles = [];
            foreach ($user->getRoles() as $userRole) {
                if ($systemRoles[$userRole]) {
                    $userRoles[] = $systemRoles[$userRole];
                }
            }

            $status = $user->isActive()?$this->trans('commands.common.status.enabled'):$this->trans('commands.common.status.disabled');
            $tableRows[] = [
                $userId,
                $user->getUsername(),
                implode(', ', $userRoles),
                $status
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows);
    }

    //@TODO: this should be in src/Command/Shared/CommandTrait.php
    public function splitOption($option)
    {
        if (1 == count($option) && strpos($option[0], " ") >= 1) {
            return explode(" ", $option[0]);
        } else {
            return $option;
        }
    }
}
