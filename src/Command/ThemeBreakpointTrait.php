<?php

/**
 * @file
 * Contains Drupal\Console\Command\ThemeBreakpointTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

trait ThemeBreakpointTrait
{
    /**
     * @param DrupalStyle $output
     *
     * @return mixed
     */
    public function breakpointQuestion(DrupalStyle $output)
    {
        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();

        $breakpoints = [];
        while (true) {
            $breakPointName = $output->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-name'),
                'narrow',
                function ($breakPointName) use ($validators) {
                    return $validators->validateMachineName($breakPointName);
                }
            );

            $breakPointLabel = $stringUtils->createMachineName($breakPointName);
            $breakPointLabel = $output->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-label'),
                $breakPointLabel,
                function ($breakPointLabel) use ($validators) {
                    return $validators->validateMachineName($breakPointLabel);
                }
            );

            $breakPointMediaQuery = $output->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-media-query'),
                'all and (min-width: 560px) and (max-width: 850px)'
            );

            $breakPointWeight = $output->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-weight'),
                '1'
            );

            $breakPointMultipliers = $output->ask(
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

            if (!$output->confirm(
                $this->trans('commands.generate.theme.questions.breakpoint-add'),
                true
            )) {
                break;
            }
        }

        return $breakpoints;
    }
}
