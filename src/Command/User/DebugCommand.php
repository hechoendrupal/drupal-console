<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DebugCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\User
 */
class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var QueryFactory
     */
    protected $entityQuery;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * DebugCommand constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param QueryFactory               $entityQuery
     * @param DrupalApi                  $drupalApi
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        QueryFactory $entityQuery,
        DrupalApi $drupalApi
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityQuery = $entityQuery;
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:debug')
            ->setDescription($this->trans('commands.user.debug.description'))
            ->addOption(
                'uid',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.user.debug.options.uid')
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.user.debug.options.username')
            )
            ->addOption(
                'mail',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.user.debug.options.mail')
            )
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

        $uids = $this->splitOption($input->getOption('uid'));
        $usernames = $this->splitOption($input->getOption('username'));
        $mails = $this->splitOption($input->getOption('mail'));

        $userStorage = $this->entityTypeManager->getStorage('user');
        $systemRoles = $this->drupalApi->getRoles();

        $query = $this->entityQuery->get('user');
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
