<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ThemeBreakpointTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Style\DrupalStyle;

trait ThemeBreakpointTrait
{
    /**
     * @param DrupalStyle $io
     *
     * @return mixed
     */
    public function breakpointQuestion(DrupalStyle $io)
    {
        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();

        $breakpoints = [];
        while (true) {
            $breakPointName = $io->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-name'),
                'narrow',
                function ($breakPointName) use ($validators) {
                    return $validators->validateMachineName($breakPointName);
                }
            );

            $breakPointLabel = $stringUtils->createMachineName($breakPointName);
            $breakPointLabel = $io->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-label'),
                $breakPointLabel,
                function ($breakPointLabel) use ($validators) {
                    return $validators->validateMachineName($breakPointLabel);
                }
            );

            $breakPointMediaQuery = $io->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-media-query'),
                'all and (min-width: 560px) and (max-width: 850px)'
            );

            $breakPointWeight = $io->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-weight'),
                '1'
            );

            $breakPointMultipliers = $io->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-multipliers'),
                '1x'
            );

            array_push(
                $breakpoints,
                [
                    'breakpoint_name' => $breakPointName,
                    'breakpoint_label' => $breakPointLabel,
                    'breakpoint_media_query' => $breakPointMediaQuery,
                    'breakpoint_weight' => $breakPointWeight,
                    'breakpoint_multipliers' => $breakPointMultipliers
                ]
            );

            if (!$io->confirm(
                $this->trans('commands.generate.theme.questions.breakpoint-add'),
                true
            )) {
                break;
            }
        }

        return $breakpoints;
    }
}
