<?php

/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\ThemeRegionTrait.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ThemeRegionTrait
{
    /**
     * @param OutputInterface $output
     * @param HelperInterface $dialog
     *
     * @return mixed
     */
    public function regionQuestion(OutputInterface $output, HelperInterface $dialog)
    {
        $stringUtils = $this->getHelperSet()->get('stringUtils');
        $validators = $this->getHelperSet()->get('validators');

        $regions = [];
        while (true) {
            $region_name = $dialog->ask(
              $output,
              $dialog->getQuestion(
                $this->trans('commands.generate.theme.questions.region-name'),
                'Content'
              ),
              'Content'
            );

            $region_machine_name = $stringUtils->createMachineName($region_name);
            $region_machine_name = $dialog->askAndValidate(
              $output,
              $dialog->getQuestion($this->trans('commands.generate.theme.questions.region-machine-name'), $region_machine_name),
              function ($region_machine_name) use ($validators) {
                  return $validators->validateMachineName($region_machine_name);
              },
              false,
              $region_machine_name,
              null
            );

            array_push($permissions, array(
              'region-name' => $region_name,
              'region-machine-name' => $region_machine_name,
            ));

            if (!$dialog->askConfirmation(
              $output,
              $dialog->getQuestion(
                $this->trans('commands.generate.theme.questions.region-add'),
                'yes',
                '?'
              ),
              true
            )
            ) {
                break;
            }
        }

        return $regions;
    }
}
