<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ConfirmationTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class ConfirmationTrait
 *
 * @package Drupal\Console\Command
 */
trait ConfirmationTrait
{
    /**
     *
     * @return bool
     */
    public function confirmGeneration()
    {
        $input = $this->getIo()->getInput();
        $yes = $input->hasOption('yes') ? $input->getOption('yes') : false;
        if ($yes) {
            return $yes;
        }

        $confirmation = $this->getIo()->confirm(
            $this->trans('commands.common.questions.confirm'),
            true
        );

        if (!$confirmation) {
            $this->getIo()->warning($this->trans('commands.common.messages.canceled'));
        }

        return $confirmation;
    }
}
