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

      /**
   * @param DrupalStyle $io
   *
   * @return mixed
   */
    public function libraryQuestion(DrupalStyle $io)
    {
        $validators = $this->validator;
        $libraries = [];
        while (true) {
            $libraryName = $io->ask(
                $this->trans('commands.generate.theme.questions.library-name')
            );
            
            $libraryVersion = $io->ask(
                $this->trans('commands.generate.theme.questions.library-version'),
                '1.0'
            );
            
            array_push(
                $libraries,
                [
                    'library_name' => $libraryName,
                    'library_version'=> $libraryVersion,
                ]
            );

            if (!$io->confirm(
                $this->trans('commands.generate.theme.questions.library-add'),
                true
            )
            ) {
                break;
            }
        }

        return $libraries;
    }
}
