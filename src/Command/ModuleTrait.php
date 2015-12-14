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
     * @return string
     * @throws \Exception
     */
    public function moduleQuestion(DrupalStyle $io)
    {
        $modules = $this->getSite()->getModules(false, false, false, true, true);

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
