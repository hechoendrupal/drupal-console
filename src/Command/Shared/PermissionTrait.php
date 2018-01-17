<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\PermissionsTrait.
 */

namespace Drupal\Console\Command\Shared;

trait PermissionTrait
{
    /**
     *
     * @return mixed
     */
    public function permissionQuestion()
    {
        $permissions = [];
        $boolOrNone = ['true','false','none'];
        while (true) {
            $permission = $this->getIo()->ask(
                $this->trans('commands.generate.permissions.questions.permission'),
                $this->trans('commands.generate.permissions.suggestions.access-content')
            );
            $title = $this->getIo()->ask(
                $this->trans('commands.generate.permissions.questions.title'),
                $this->trans('commands.generate.permissions.suggestions.access-content')
            );
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.permissions.questions.description'),
                $this->trans('commands.generate.permissions.suggestions.allow-access-content')
            );
            $restrictAccess = $this->getIo()->choiceNoList(
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

            if (!$this->getIo()->confirm(
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
