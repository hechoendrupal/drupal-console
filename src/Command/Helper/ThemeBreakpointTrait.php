<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\ThemeBreakpointTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ThemeBreakpointTrait
{
    /**
   * @param OutputInterface $output
   * @param HelperInterface $dialog
   *
   * @return mixed
   */
    public function breakpointQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        $stringUtils = $this->getHelperSet()->get('stringUtils');
        $validators = $this->getHelperSet()->get('validators');

        $breakpoints = [];
        while (true) {
            $breakpoint_name = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.questions.breakpoint-name'), 'narrow'),
                function ($breakpoint_name) use ($validators) {
                    return $validators->validateMachineName($breakpoint_name);
                },
                false,
                'narrow',
                null
            );

            $breakpoint_label = $stringUtils->createMachineName($breakpoint_name);
            $breakpoint_label = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.generate.theme.questions.breakpoint-label'), $breakpoint_label),
                function ($breakpoint_label) use ($validators) {
                    return $validators->validateMachineName($breakpoint_label);
                },
                false,
                $breakpoint_label,
                null
            );

            $breakpoint_media_query = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.theme.questions.breakpoint-media-query'),
                    'all and (min-width: 560px) and (max-width: 850px)'
                ),
                'all and (min-width: 560px) and (max-width: 850px)'
            );

            $breakpoint_weight = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.theme.questions.breakpoint-weight'),
                    '1'
                ),
                '1'
            );

            $breakpoint_multipliers = $dialog->ask(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.theme.questions.breakpoint-multipliers'),
                    '1x'
                ),
                '1x'
            );

            array_push(
                $breakpoints, array(
                'breakpoint_name' => $breakpoint_name,
                'breakpoint_label' => $breakpoint_label,
                'breakpoint_media_query' => $breakpoint_media_query,
                'breakpoint_weight' => $breakpoint_weight,
                'breakpoint_multipliers' => $breakpoint_multipliers
                )
            );

            if (!$dialog->askConfirmation(
                $output,
                $dialog->getQuestion(
                    $this->trans('commands.generate.theme.questions.breakpoint-add'),
                    'yes',
                    '?'
                ),
                true
            )
            ) {
                break;
            }
        }

        return $breakpoints;
    }
}
