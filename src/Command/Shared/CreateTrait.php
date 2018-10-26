<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\CreateTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class CreateTrait
 *
 * @package Drupal\Console\Command
 */
trait CreateTrait
{
    /**
     * @return array
     */
    private function getTimeRange()
    {
        $timeRanges = [
            1 => sprintf('N | %s', $this->trans('commands.create.nodes.questions.time-ranges.0')),
            3600 => sprintf('H | %s', $this->trans('commands.create.nodes.questions.time-ranges.1')),
            86400 => sprintf('D | %s', $this->trans('commands.create.nodes.questions.time-ranges.2')),
            604800 => sprintf('W | %s', $this->trans('commands.create.nodes.questions.time-ranges.3')),
            2592000 => sprintf('M | %s', $this->trans('commands.create.nodes.questions.time-ranges.4')),
            31536000 => sprintf('Y | %s', $this->trans('commands.create.nodes.questions.time-ranges.5'))
        ];

        return $timeRanges;
    }
}
