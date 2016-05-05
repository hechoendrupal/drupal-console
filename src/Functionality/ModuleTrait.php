<?php

/**
 * @file
 * Contains Drupal\Console\Command\ModuleTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

/**
 * Class ModuleTrait
 * @package Drupal\Console\Command
 */
trait ModuleTrait
{
    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param bool|true                         $showProfile
     * @return string
     * @throws \Exception
     */
    public function moduleQuestion(DrupalStyle $io, $showProfile = true)
    {
        $modules = $this->getSite()->getModules(false, true, true, false, true, true);

        if ($showProfile) {
            $modules[] = $this->getSite()->getProfile(false, true);
        }

        if (empty($modules)) {
            throw new \Exception('No modules available, execute `generate:module` command to generate one.');
        }

        $module = $io->choiceNoList(
            $this->trans('commands.common.questions.module'),
            $modules
        );

        return $module;
    }
}
