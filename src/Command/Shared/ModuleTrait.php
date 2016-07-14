<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ModuleTrait.
 */

namespace Drupal\Console\Command\Shared;

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
        $modules = $this->getApplication()->getSite()->getModules(false, true, true, false, true, true);

        if ($showProfile) {
            $modules[] = $this->getApplication()->getSite()->getProfile(false, true);
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

    public function moduleRequirement($module)
    {
        foreach ($module as $module_name) {
            module_load_install($module_name);

            if ($requirements = \Drupal::moduleHandler()->invoke($module_name, 'requirements', array('install'))) {
                foreach ($requirements as $requirement) {
                    throw new \Exception($module_name .' can not be installed: ' . $requirement['description']);
                }
            }
        }
    }
}
