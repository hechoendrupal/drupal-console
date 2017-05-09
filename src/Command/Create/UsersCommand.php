<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\UsersCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\CreateTrait;
use Drupal\Console\Utils\Create\UserData;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class UsersCommand
 *
 * @package Drupal\Console\Command\Create
 */
class UsersCommand extends Command
{
    use CreateTrait;
    use CommandTrait;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;
    /**
     * @var UserData
     */
    protected $createUserData;

    /**
     * UsersCommand constructor.
     *
     * @param DrupalApi $drupalApi
     * @param UserData  $createUserData
     */
    public function __construct(
        DrupalApi $drupalApi,
        UserData $createUserData
    ) {
        $this->drupalApi = $drupalApi;
        $this->createUserData = $createUserData;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:users')
            ->setDescription($this->trans('commands.create.users.description'))
            ->addArgument(
                'roles',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.users.arguments.roles')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.users.options.limit')
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.users.options.password')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.users.options.time-range')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $rids = $input->getArgument('roles');
        if (!$rids) {
            $roles = $this->drupalApi->getRoles();
            $rids = $io->choice(
                $this->trans('commands.create.users.questions.roles'),
                array_values($roles),
                null,
                true
            );

            $rids = array_map(
                function ($role) use ($roles) {
                    return array_search($role, $roles);
                },
                $rids
            );

            $input->setArgument('roles', $rids);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.users.questions.limit'),
                10
            );
            $input->setOption('limit', $limit);
        }

        $password = $input->getOption('password');
        if (!$password) {
            $password = $io->ask(
                $this->trans('commands.create.users.questions.password'),
                5
            );

            $input->setOption('password', $password);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
                $this->trans('commands.create.nodes.questions.time-range'),
                array_values($timeRanges)
            );

            $input->setOption('time-range', array_search($timeRange, $timeRanges));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $roles = $input->getArgument('roles');
        $limit = $input->getOption('limit')?:25;
        $password = $input->getOption('password');
        $timeRange = $input->getOption('time-range')?:31536000;

        if (!$roles) {
            $roles = $this->drupalApi->getRoles();
        }

        $users = $this->createUserData->create(
            $roles,
            $limit,
            $password,
            $timeRange
        );

        $tableHeader = [
          $this->trans('commands.create.users.messages.user-id'),
          $this->trans('commands.create.users.messages.username'),
          $this->trans('commands.create.users.messages.roles'),
          $this->trans('commands.create.users.messages.created'),
        ];

        if ($users['success']) {
            $io->table($tableHeader, $users['success']);

            $io->success(
                sprintf(
                    $this->trans('commands.create.users.messages.created-users'),
                    $limit
                )
            );
        }

        return 0;
    }
}
