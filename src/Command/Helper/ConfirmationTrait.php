<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\Confirmation.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

trait ConfirmationTrait
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function confirmationQuestion(InputInterface $input, OutputInterface $output, HelperInterface $dialog)
    {
        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation(
                $output,
                $dialog->getQuestion($this->trans('commands.common.questions.confirm'), 'yes', '?'),
                true
            )
            ) {
                $output->writeln('<error>'.$this->trans('commands.common.messages.canceled').'</error>');

                return true;
            }

            return false;
        }

        return false;
    }
}
