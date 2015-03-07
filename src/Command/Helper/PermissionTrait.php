<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\PermissionsTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait PermissionTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     * @return mixed
     */
    public function permissionQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        if ($dialog->askConfirmation(
          $output,
          $dialog->getQuestion($this->trans('commands.common.questions.inputs.permission'), 'yes', '?'),
          true
        )
        ) {
            $permissions = [];
            while (true) {
                $permission = $dialog->ask(
                  $output,
                  $dialog->getQuestion($this->trans('commands.generate.permission.options.permission'),
                    'Access Content'),
                  null
                );

                if (empty($permission)) {
                    break;
                }
                $permission = $this->getStringUtils()->camelCaseToLowerCase($permission);
                $permission_title = $this->getStringUtils()->camelCaseToUcFirst($permission);

                array_push($permissions, array(
                  'permission' => $permission,
                  'permission_title' => $permission_title,
                ));
            }

            return $permissions;
        }
        return null;
    }
}
