<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\RoleCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\User
 */
class RoleCommand extends Command
{
    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * RoleCommand constructor.
     *
     * @param DrupalApi $drupalApi
     */
    public function __construct(DrupalApi $drupalApi)
    {
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:role')
            ->setDescription($this->trans('commands.user.role.description'))
            ->addArgument(
                'operation',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.user.role.arguments.operation')
            )
            ->addArgument(
                'user',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.user.role.arguments.user')
            )
            ->addArgument(
                'role',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.user.role.arguments.roles')
            )->setAliases(['ur']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = $input->getArgument('operation');
        $user = $input->getArgument('user');
        $role = $input->getArgument('role');

        if (!$operation || !$user || !$role) {
            throw new \Exception(
                $this->trans('commands.user.role.messages.bad-arguments')
            );
        }

        $systemRoles = $this->drupalApi->getRoles();

        if (is_numeric($user)) {
            $userObject = user_load($user);
        } else {
            $userObject = user_load_by_name($user);
        }


        if (!is_object($userObject)) {
            if (!filter_var($user, FILTER_VALIDATE_EMAIL) === false) {
                $userObject = user_load_by_mail($user);
            }
        }

        if (!is_object($userObject)) {
            $this->getIo()->error(sprintf($this->trans('commands.user.role.messages.no-user-found'), $user));
            return 1;
        }

        if (!array_key_exists($role, $systemRoles)) {
            $this->getIo()->error(sprintf($this->trans('commands.user.role.messages.no-role-found'), $role));
            return 1;
        }

        if ("add" == $operation) {
            $userObject->addRole($role);
            $userObject->save();
            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.user.role.messages.add-success'),
                    $userObject->name->value . " (" . $userObject->mail->value . ") ",
                    $role
                )
            );
        }

        if ("remove" == $operation) {
            $userObject->removeRole($role);
            $userObject->save();

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.user.role.messages.remove-success'),
                    $userObject->name->value . " (" . $userObject->mail->value . ") ",
                    $role
                )
            );
        }
    }
}
