<?php

/**
 * @file
 * Contains \Drupal\Console\Command\PermissionDebugCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DebugCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class PermissionCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('debug:permission')
            ->setDescription($this->trans('commands.debug.permission.description'))
            ->setHelp($this->trans('commands.debug.permission.help'))
            ->addArgument(
                'role',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.permission.arguments.role')
            )->setAliases(['dp']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $role = $input->getArgument('role');
        // No role specified, show a list of ALL permissions.
        if (!$role) {
            $tableHeader = [
                $this->trans('commands.debug.permission.table-headers.permission-name'),
                $this->trans('commands.debug.permission.table-headers.permission-label'),
                $this->trans('commands.debug.permission.table-headers.permission-role')
            ];
            $tableRows = [];
            $permissions = \Drupal::service('user.permissions')->getPermissions();
            foreach ($permissions as $permission_name => $permission) {
                $tableRows[$permission_name] = [
                    $permission_name,
                    strip_tags($permission['title']->__toString()),
                    implode(', ', $this->getRolesAssignedByPermission($permission_name))
                ];
            }

            ksort($tableRows);
            $io->table($tableHeader, array_values($tableRows));

            return true;
        } else {
            $tableHeader = [
                $this->trans('commands.debug.permission.table-headers.permission-name'),
                $this->trans('commands.debug.permission.table-headers.permission-label')
            ];
            $tableRows = [];
            $permissions = \Drupal::service('user.permissions')->getPermissions();
            $roles = user_roles();
            if (empty($roles[$role])) {
                $message = sprintf($this->trans('commands.debug.permission.messages.role-error'), $role);
                $io->error($message);
                return true;
            }
            $user_permission = $roles[$role]->getPermissions();
            foreach ($permissions as $permission_name => $permission) {
                if (in_array($permission_name, $user_permission)) {
                    $tableRows[$permission_name] = [
                      $permission_name,
                      strip_tags($permission['title']->__toString())
                    ];
                }
            }
            ksort($tableRows);
            $io->table($tableHeader, array_values($tableRows));
            return true;
        }
    }

    /**
     * Get user roles Assigned by Permission.
     *
     * @param string $permission_name
     *   Permission Name.
     *
     * @return array
     *   User roles filtered by permission else empty array.
     */
    public function getRolesAssignedByPermission($permission_name)
    {
        $roles = user_roles();
        $roles_found = [];
        foreach ($roles as $role) {
            if ($role->hasPermission($permission_name)) {
                $roles_found[] = $role->getOriginalId();
            }
        }
        return $roles_found;
    }
}
