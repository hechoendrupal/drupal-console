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
                    'access content'),
                  'access content'
                );
                $title = $dialog->ask(
                  $output,
                  $dialog->getQuestion($this->trans('commands.generate.permission.options.title'),
                    'Access Content'),
                  'Access Content'
                );
                $description = $dialog->ask(
                  $output,
                  $dialog->getQuestion($this->trans('commands.generate.permission.options.description'),
                    'Allow access to my content'),
                  'Allow access to my content'
                );
                $restrictAccess = $dialog->ask(
                  $output,
                  $dialog->getQuestion($this->trans('commands.generate.permission.options.restrict-access'), 'false', '?'),
                  'false'
                );
                if (!$dialog->askConfirmation(
                  $output,
                  $dialog->getQuestion($this->trans('commands.generate.permission.questions.add'), 'y', '?'),
                  true
                )) {
                    break;
                }

                if (empty($permission)) {

                }
                $permission = $this->getStringUtils()->camelCaseToLowerCase($permission);
                $title = $this->getStringUtils()->camelCaseToUcFirst($title);

                array_push($permissions, array(
                  'permission' => $permission,
                  'title' => $title,
                  'description' => $description,
                  'restrict_access' => $restrictAccess,
                ));
            }

            return $permissions;
        }
        return null;
    }
}
