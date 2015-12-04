<?php

/**
 * @file
 * Contains Drupal\Console\Command\ThemeRegionTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

trait ThemeRegionTrait
{
    /**
   * @param DrupalStyle $output
   *
   * @return mixed
   */
    public function regionQuestion(DrupalStyle $output)
    {
        $stringUtils = $this->getStringHelper();
        $validators = $this->getValidator();

        $regions = [];
        while (true) {
            $regionName = $output->ask(
                $this->trans('commands.generate.theme.questions.region-name'),
                'Content'
            );

            $regionMachineName = $stringUtils->createMachineName($regionName);
            $regionMachineName = $output->ask(
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

            if (!$output->confirm(
                $this->trans('commands.generate.theme.questions.region-add'),
                true
            )) {
                break;
            }
        }

        return $regions;
    }
}
