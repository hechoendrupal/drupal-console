<?php

/**
 * @file
 * Contains Drupal\Console\Command\Confirmation.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

/**
 * Class ConfirmationTrait
 * @package Drupal\Console\Command
 */
trait ConfirmationTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return bool
     */
    public function confirmGeneration(DrupalStyle $output)
    {
        $confirmation = $output->confirm(
            $this->trans('commands.common.questions.confirm'),
            true
        );

        if (!$confirmation) {
            $output->warning($this->trans('commands.common.messages.canceled'));
        }

        return $confirmation;
    }
}
