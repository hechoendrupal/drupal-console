<?php
/**
 * PHP version 7.0
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace Drupal\Console\Command\Generate\Questions;

use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Utils\TranslatorManager;

class ConfirmGeneration
{
    /** @var DrupalStyle */
    private $io;

    /** @var TranslatorManager */
    private $translator;

    /**
     * @param DrupalStyle $io
     * @param TranslatorManager $translator
     */
    public function __construct(DrupalStyle $io, TranslatorManager $translator)
    {
        $this->io = $io;
        $this->translator = $translator;
    }

    /**
     * @param bool $yes
     * @return bool
     */
    public function confirm($yes = false)
    {
        if ($yes) {
            return $yes;
        }

        $confirmation = $this->io->confirm(
            $this->translator->trans('commands.common.questions.confirm'),
            true
        );

        if (!$confirmation) {
            $this->io->warning(
                $this->translator->trans('commands.common.messages.canceled')
            );
        }

        return $confirmation;
    }
}
