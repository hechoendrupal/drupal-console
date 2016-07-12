<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\RoleCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\User
 */
class RoleCommand extends Command
{
    use ContainerAwareCommandTrait;

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
                $this->trans('commands.user.role.operation')
            )
            ->addArgument(
                'user',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.user.role.user')
            )
            ->addArgument(
                'role',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.user.role.role')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $operation = $input->getArgument('operation');
        $user = $input->getArgument('user');
        $role = $input->getArgument('role');

        if (!$operation || !$user || !$role) {
            throw new \Exception(
                $this->trans('commands.user.role.messages.bad-arguments')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $operation = $input->getArgument('operation');
        $user = $input->getArgument('user');
        $role = $input->getArgument('role');

        $systemRoles = $this->getApplication()->getDrupalApi()->getRoles();

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
            $io->error(sprintf($this->trans('commands.user.role.messages.no-user-found'), $user));
            return 1;
        }

        if (!array_key_exists($role, $systemRoles)) {
            $io->error(sprintf($this->trans('commands.user.role.messages.no-role-found'), $role));
            return 1;
        }

        if ("add" == $operation) {
            $userObject->addRole($role);
            $userObject->save();
            $io->success(
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

            $io->success(
                sprintf(
                    $this->trans('commands.user.role.messages.remove-success'),
                    $userObject->name->value . " (" . $userObject->mail->value . ") ",
                    $role
                )
            );
        }
    }
}
