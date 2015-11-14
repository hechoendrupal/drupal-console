<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\ConnectTrait.
 */

namespace Drupal\Console\Command\Locale;

trait LocaleTrait
{
    protected function getLanguages()
    {
        $drupal = $this->getDrupalHelper();
        $languages = $drupal->getStandardLanguages();

        return $languages;
    }

    protected function getDefaultLanguage()
    {
        $application = $this->getApplication();
        $config = $application->getConfig();
        return $config->get('application.language');
    }
}
