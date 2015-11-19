<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Locale\LanguageSyncCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\Locale\LocaleTrait;

class TranslationSyncCommand extends ContainerAwareCommand
{
    use LocaleTrait;

    protected function configure()
    {
        $this
            ->setName('locale:translation:sync')
            ->setDescription($this->trans('commands.locale.translation.sync.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.locale.translation.sync.arguments.language')
            );

        $this->addDependency('locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $this->getMessageHelper();
        $moduleHandler = $this->getModuleHandler();
        $state = $this->getState();

        $language_filter = $input->getArgument('language');

        $moduleHandler->loadInclude('locale', 'inc', 'locale.compare');
        $moduleHandler->loadInclude('locale', 'inc', 'locale.fetch');
        $moduleHandler->loadInclude('locale', 'inc', 'locale.batch');
        $moduleHandler->loadInclude('locale', 'inc', 'locale.bulk');

        $languages = locale_translatable_language_list();
        $projectsStatus = $this->projectsStatus();

        if($language_filter) {
            $langcodes = array();
            if(isset($languages[$language_filter])) {
                $langcodes = array($language_filter => $language_filter);
            } else {
                foreach($languages as $langcode => $language) {
                    if($language->getName() == $language_filter) {
                        $langcodes = array($langcode => $langcode);
                    }
                }
            }

            if(empty($langcodes)) {

                $message->addErrorMessage(
                    sprintf(
                        $this->trans('commands.locale.translation.sync.messages.invalid-language'),
                        $language_filter
                    )
                );
                return;
            }
        } else {
            $langcodes = array_combine(array_keys($projectsStatus), array_keys($projectsStatus));
        }

        // Set the translation import options. This determines if existing
        // translations will be overwritten by imported strings.
        $options = _locale_translation_default_update_options();
        $projects = [];

        // If the status was updated recently we can immediately start fetching the
        // translation updates. If the status is expired we clear it an run a batch to
        // update the status and then fetch the translation updates.
        $last_checked = $state->get('locale.translation_last_checked');
        if ($last_checked < REQUEST_TIME - LOCALE_TRANSLATION_STATUS_TTL) {
            locale_translation_clear_status();
            $batch = locale_translation_batch_update_build(array(), $langcodes, $options);
            batch_set($batch);
        } else {
            // Set a batch to download and import translations.
            $batch = locale_translation_batch_fetch_build($projects, $langcodes, $options);
            batch_set($batch);
            // Set a batch to update configuration as well.
            if ($batch = locale_config_batch_update_components($options, $langcodes)) {
                batch_set($batch);
            }
        }

        $batch =& batch_get();

        try {
            foreach ($batch['sets'][0]['operations'] as $operation) {
                $context = array();
                $params = $operation[1];
                $params['context'] = &$context;
                $project = $params[0];
                $language = $languages[$params[1]];

                switch ($operation[0]) {
                    case 'locale_translation_batch_status_check':
                        $message->addInfoMessage(
                            sprintf(
                                $this->trans('commands.locale.translation.sync.messages.checking'),
                                $language->getName(),
                                $project
                            )
                        );
                        break;
                    case 'locale_translation_batch_fetch_download':
                        $message->addInfoMessage(
                            sprintf(
                                $this->trans('commands.locale.translation.sync.messages.downloading'),
                                $language->getName(),
                                $project
                            )
                        );
                        break;
                    case 'locale_translation_batch_fetch_import':
                        /*$message->showMessage(
                            $output,
                            sprintf(
                                $this->trans('commands.locale.translation.sync.messages.importing'),
                                $language->getName(),
                                $project
                            ),
                            self::MESSAGE_INFO
                        );*/
                        $message->addInfoMessage(
                            sprintf(
                                $this->trans('commands.locale.translation.sync.messages.importing'),
                                $language->getName(),
                                $project
                            )
                        );
                        break;
                }

                call_user_func_array($operation[0], $params);
                if ($context['results']['failed_files']) {
                    $message->addErrorMessage(
                        $context['results']['failed_files']
                    );
                }

                //print_r($context);
                //return;
            }
        } catch (Exception $e) {
            $message->addErrorMessage(
                $e->getMessage()
            );
        }
    }
}
