<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\PermissionsTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

trait PermissionTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function permissionQuestion(DrupalStyle $output)
    {
        $permissions = [];
        $boolOrNone = ['true','false','none'];
        while (true) {
            $permission = $output->ask(
                $this->trans('commands.generate.permissions.questions.permission'),
                $this->trans('commands.generate.permissions.suggestions.access-content')
            );
            $title = $output->ask(
                $this->trans('commands.generate.permissions.questions.title'),
                $this->trans('commands.generate.permissions.suggestions.access-content')
            );
            $description = $output->ask(
                $this->trans('commands.generate.permissions.questions.description'),
                $this->trans('commands.generate.permissions.suggestions.allow-access-content')
            );
            $restrictAccess = $output->choiceNoList(
                $this->trans('commands.generate.permissions.questions.restrict-access'),
                $boolOrNone,
                'none'
            );

            $permission = $this->stringConverter->camelCaseToLowerCase($permission);
            $title = $this->stringConverter->anyCaseToUcFirst($title);

            array_push(
                $permissions,
                [
                    'permission' => $permission,
                    'title' => $title,
                    'description' => $description,
                    'restrict_access' => $restrictAccess,
                ]
            );

            if (!$output->confirm(
                $this->trans('commands.generate.permissions.questions.add'),
                true
            )
            ) {
                break;
            }
        }

        return $permissions;
    }
}
