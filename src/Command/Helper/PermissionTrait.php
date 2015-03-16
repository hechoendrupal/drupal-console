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
     *
     * @return mixed
     */
    public function permissionQuestion(
      OutputInterface $output,
      HelperInterface $dialog
    ) {
        $permissions = [];
        while (true) {
            $permission = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.permission.questions.permission'),
                'access content'),
              'access content'
            );
            $title = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.permission.questions.title'),
                'Access content'),
              'Access content'
            );
            $description = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.permission.questions.description'),
                'Allow access to my content'),
              'Allow access to my content'
            );
            $restrictAccess = $dialog->ask(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.permission.questions.restrict-access'),
                'false', '?'),
              'false'
            );

            $permission = $this->getStringUtils()->camelCaseToLowerCase($permission);
            $title = $this->getStringUtils()->anyCaseToUcFirst($title);

            array_push($permissions, array(
              'permission' => $permission,
              'title' => $title,
              'description' => $description,
              'restrict_access' => $restrictAccess,
            ));

            if (!$dialog->askConfirmation(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.permission.questions.add'),
                'y', '?'),
              true
            )
            ) {
                break;
            }
        }

        return $permissions;
    }
}
