<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ConfirmationTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ConfirmationTrait
 *
 * @package Drupal\Console\Command
 */
trait ConfirmationTrait
{
    /**
     * @param DrupalStyle $io
     * @param bool        $yes
     *
     * @return bool
     */
    public function confirmGeneration(DrupalStyle $io, $yes = false)
    {
        if ($yes) {
            return $yes;
        }

        $confirmation = $io->confirm(
            $this->trans('commands.common.questions.confirm'),
            true
        );

        if (!$confirmation) {
            $io->warning($this->trans('commands.common.messages.canceled'));
        }

        return $confirmation;
    }
}
