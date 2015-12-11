<?php

/**
 * @file
 * Contains Drupal\Console\Command\ModuleTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

trait ModuleTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function moduleQuestion(DrupalStyle $output)
    {
        $modules = $this->getSite()->getModules(false, false, false, true, true);

        $module = $output->choiceNoList(
            $this->trans('commands.common.questions.module'),
            $modules
        );

        return $module;
    }
}
