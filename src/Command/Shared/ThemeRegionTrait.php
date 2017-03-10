<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ThemeRegionTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;

trait ThemeRegionTrait
{
    /**
   * @param DrupalStyle $io
   *
   * @return mixed
   */
    public function regionQuestion(DrupalStyle $io)
    {
        $validators = $this->validator;
        $regions = [];
        while (true) {
            $regionName = $io->ask(
                $this->trans('commands.generate.theme.questions.region-name'),
                'Content'
            );

            $regionMachineName = $this->stringConverter->createMachineName($regionName);
            $regionMachineName = $io->ask(
                $this->trans('commands.generate.theme.questions.region-machine-name'),
                $regionMachineName,
                function ($regionMachineName) use ($validators) {
                    return $validators->validateMachineName($regionMachineName);
                }
            );

            array_push(
                $regions,
                [
                    'region_name' => $regionName,
                    'region_machine_name' => $regionMachineName,
                ]
            );

            if (!$io->confirm(
                $this->trans('commands.generate.theme.questions.region-add'),
                true
            )
            ) {
                break;
            }
        }

        return $regions;
    }
}
