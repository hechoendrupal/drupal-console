<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ThemeBreakpointTrait.
 */

namespace Drupal\Console\Command\Shared;

trait ThemeBreakpointTrait
{
    /**
     *
     * @return mixed
     */
    public function breakpointQuestion()
    {
        $stringUtils = $this->stringConverter;
        $validators = $this->validator;

        $breakpoints = [];
        while (true) {
            $breakPointName = $this->getIo()->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-name'),
                'narrow',
                function ($breakPointName) use ($validators) {
                    return $validators->validateMachineName($breakPointName);
                }
            );

            $breakPointLabel = $stringUtils->createMachineName($breakPointName);
            $breakPointLabel = $this->getIo()->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-label'),
                $breakPointLabel,
                function ($breakPointLabel) use ($validators) {
                    return $validators->validateMachineName($breakPointLabel);
                }
            );

            $breakPointMediaQuery = $this->getIo()->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-media-query'),
                'all and (min-width: 560px) and (max-width: 850px)'
            );

            $breakPointWeight = $this->getIo()->ask(
                $this->trans('commands.generate.theme.questions.breakpoint-weight'),
                '1'
            );

            $breakPointMultipliers = $this->getIo()->ask(
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

            if (!$this->getIo()->confirm(
                $this->trans('commands.generate.theme.questions.breakpoint-add'),
                true
            )
            ) {
                break;
            }
        }

        return $breakpoints;
    }
}
