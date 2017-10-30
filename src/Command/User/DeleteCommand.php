<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\DeleteCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class DeleteCommand
 *
 * @package Drupal\Console\Command\User
 */
class DeleteCommand extends UserBase
{
    /**
     * @var QueryFactory
     */
    protected $entityQuery;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * DeleteCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param QueryFactory               $entityQuery
     * @param DrupalApi                  $drupalApi
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        QueryFactory $entityQuery,
        DrupalApi $drupalApi
    ) {
        $this->entityQuery = $entityQuery;
        $this->drupalApi = $drupalApi;
        parent::__construct($entityTypeManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:delete')
            ->setDescription($this->trans('commands.user.delete.description'))
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.delete.options.user')
            )
            ->addOption(
                'roles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.delete.options.roles')
            )->setAliases(['ud']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $user = $input->getOption('user');
        if (!$user) {
            $user = $io->askEmpty(
                $this->trans('commands.user.delete.questions.user'),
                null
            );
            $input->setOption('user', $user);
        }

        $roles = $input->getOption('roles');

        if (!$user && !$roles) {
            $systemRoles = $this->drupalApi->getRoles(false, false, false);
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

        $user = $input->getOption('user');

        if ($user) {
            $userEntity = $this->getUserEntity($user);
            if (!$userEntity) {
                $io->error(
                    sprintf(
                        $this->trans('commands.user.delete.errors.invalid-user'),
                        $user
                    )
                );

                return 1;
            }

            if ($userEntity->id() <= 1) {
                $io->error(
                    sprintf(
                        $this->trans('commands.user.delete.errors.invalid-user'),
                        $user
                    )
                );

                return 1;
            }

            try {
                $userEntity->delete();
                $io->info(
                    sprintf(
                        $this->trans('commands.user.delete.messages.user-deleted'),
                        $userEntity->getUsername()
                    )
                );

                return 0;
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return 1;
            }
        }

        $roles = $input->getOption('roles');

        if ($roles) {
            $roles = is_array($roles)?$roles:[$roles];

            $query = $this->entityQuery
                ->get('user')
                ->condition('roles', array_values($roles), 'IN')
                ->condition('uid', 1, '>');
            $results = $query->execute();

            $users = $this->entityTypeManager
                ->getStorage('user')
                ->loadMultiple($results);

            $tableHeader = [
              $this->trans('commands.user.debug.messages.user-id'),
              $this->trans('commands.user.debug.messages.username'),
            ];

            $tableRows = [];
            foreach ($users as $user => $userEntity) {
                try {
                    $userEntity->delete();
                    $tableRows['success'][] = [$user, $userEntity->getUsername()];
                } catch (\Exception $e) {
                    $tableRows['error'][] = [$user, $userEntity->getUsername()];
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

                return 0;
            }
        }
    }
}
