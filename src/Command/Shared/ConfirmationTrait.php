<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ConfirmationTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ConfirmationTrait
 *
 * @package Drupal\Console\Command
 */
trait ConfirmationTrait
{
    /**
     * @param  DrupalStyle    $io
     *   Console interface.
     * @param  InputInterface $input
     *   Input interface.
     *
     * @return bool
     */
    public function confirmGeneration(DrupalStyle $io, InputInterface $input)
    {
        $yes = $input->hasOption('yes') ? $input->getOption('yes') : false;
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
