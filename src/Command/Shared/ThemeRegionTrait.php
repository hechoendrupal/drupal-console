<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ThemeRegionTrait.
 */

namespace Drupal\Console\Command\Shared;

trait ThemeRegionTrait
{
    /**
   *
   * @return mixed
   */
    public function regionQuestion()
    {
        $validators = $this->validator;
        $regions = [];
        while (true) {
            $regionName = $this->getIo()->ask(
                $this->trans('commands.generate.theme.questions.region-name'),
                'Content'
            );

            $regionMachineName = $this->stringConverter->createMachineName($regionName);
            $regionMachineName = $this->getIo()->ask(
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

            if (!$this->getIo()->confirm(
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
   *
   * @return mixed
   */
    public function libraryQuestion()
    {
        $libraries = [];
        while (true) {
            $libraryName = $this->getIo()->ask(
                $this->trans('commands.generate.theme.questions.library-name')
            );
            
            $libraryVersion = $this->getIo()->ask(
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

            if (!$this->getIo()->confirm(
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
