<?php

/**
 * @file
 * Contains Drupal\Console\Command\ModuleTrait.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ModuleTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function moduleQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        $modules = $this->getSite()->getModules(false, false, false, true, true);

        return $dialog->askAndValidate(
            $output,
            $dialog->getQuestion($this->trans('commands.common.questions.module'), ''),
            function ($module) {
                return $this->validateModuleExist($module);
            },
            false,
            '',
            $modules
        );
    }
}
